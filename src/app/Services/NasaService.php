<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Fetches and caches NASA POWER climatology data for a given coordinate.
 *
 * Responsibilities:
 *  - Check the DB cache (climatology_locations + climatology_monthly).
 *  - If not cached, call the NASA POWER API and persist the result.
 *  - Return a normalised array ready for JSON serialisation.
 *
 * This class has no knowledge of HTTP, superglobals, or JSON encoding.
 * All errors are surfaced as RuntimeException so the caller can decide
 * how to respond.
 */
class NasaService
{
    private const MONTH_KEYS = [
        'JAN','FEB','MAR','APR','MAY',
        'JUN','JUL','AUG','SEP','OCT','NOV','DEC',
    ];

    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns climatology data for the given coordinates.
     *
     * @param  float $lat  Latitude  (-90 … 90)
     * @param  float $lng  Longitude (-180 … 180)
     * @return array{source:string, lat:float, lng:float, tmin:float, tmax:float, monthly:array}
     *
     * @throws RuntimeException  If the NASA POWER API is unreachable or returns unexpected data.
     * @throws PDOException      If a DB operation fails.
     */
    public function getClimateData(float $lat, float $lng): array
    {
        // Round to 2 d.p. to match the DB UNIQUE constraint precision
        $lat = round($lat, 2);
        $lng = round($lng, 2);

        // ── Cache lookup ──────────────────────────────────────────────────────
        $cached = $this->findCachedLocation($lat, $lng);

        if ($cached !== null) {
            $monthly = $this->fetchMonthly($cached['id']);
            return [
                'source'  => 'cache',
                'lat'     => $lat,
                'lng'     => $lng,
                'tmin'    => (float) $cached['absolute_min_temp'],
                'tmax'    => (float) $cached['absolute_max_temp'],
                'monthly' => $monthly,
            ];
        }

        // ── NASA POWER API call ───────────────────────────────────────────────
        $monthly = $this->fetchFromNasa($lat, $lng);

        $absTmin = (float) min(array_column($monthly, 't2m_min'));
        $absTmax = (float) max(array_column($monthly, 't2m_max'));

        // ── Persist ───────────────────────────────────────────────────────────
        $this->persist($lat, $lng, $absTmin, $absTmax, $monthly);

        return [
            'source'  => 'api',
            'lat'     => $lat,
            'lng'     => $lng,
            'tmin'    => $absTmin,
            'tmax'    => $absTmax,
            'monthly' => $monthly,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Returns the cached location row or null if not found.
     *
     * @return array<string, mixed>|null
     */
    private function findCachedLocation(float $lat, float $lng): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, absolute_min_temp, absolute_max_temp
             FROM climatology_locations
             WHERE latitude = :lat AND longitude = :lng
             LIMIT 1'
        );
        $stmt->execute([':lat' => $lat, ':lng' => $lng]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Fetches the 12 monthly rows for a cached location.
     *
     * @return array<int, array<string, float>>
     */
    private function fetchMonthly(int $locationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT month,
                    ghi_kwh_m2_day AS ghi,
                    dni_kwh_m2_day AS dni,
                    dhi_kwh_m2_day AS dhi,
                    sun_hours,
                    t2m_avg, t2m_max, t2m_min, ws10m
             FROM climatology_monthly
             WHERE location_id = :id
             ORDER BY month'
        );
        $stmt->execute([':id' => $locationId]);

        return array_map(
            fn(array $row) => array_map('floatval', $row),
            $stmt->fetchAll()
        );
    }

    /**
     * Calls the NASA POWER API and returns 12 parsed monthly rows.
     *
     * @return array<int, array<string, float>>
     * @throws RuntimeException
     */
    private function fetchFromNasa(float $lat, float $lng): array
    {
        $url = sprintf(
            'https://power.larc.nasa.gov/api/temporal/climatology/point'
            . '?parameters=ALLSKY_SFC_SW_DWN,ALLSKY_SFC_SW_DNI,ALLSKY_SFC_SW_DIFF,T2M,T2M_MAX,T2M_MIN,WS10M'
            . '&community=RE&latitude=%s&longitude=%s&format=JSON',
            $lat,
            $lng
        );

        $ctx = stream_context_create(['http' => ['timeout' => 20]]);
        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            throw new RuntimeException(
                'No se pudo conectar con la API de NASA POWER. '
                . 'Intenta de nuevo o ingresa los datos manualmente.'
            );
        }

        $data   = json_decode($raw, true);
        $params = $data['properties']['parameter'] ?? null;

        if (
            !$params
            || !isset(
                $params['ALLSKY_SFC_SW_DWN'],
                $params['ALLSKY_SFC_SW_DNI'],
                $params['ALLSKY_SFC_SW_DIFF'],
                $params['T2M'],
                $params['T2M_MAX'],
                $params['T2M_MIN'],
                $params['WS10M']
            )
        ) {
            throw new RuntimeException('Respuesta inesperada de NASA POWER.');
        }

        $monthly = [];
        foreach (self::MONTH_KEYS as $i => $key) {
            $monthly[] = [
                'month'     => $i + 1,
                'ghi'       => (float) $params['ALLSKY_SFC_SW_DWN'][$key],
                'dni'       => (float) $params['ALLSKY_SFC_SW_DNI'][$key],
                'dhi'       => (float) $params['ALLSKY_SFC_SW_DIFF'][$key],
                'sun_hours' => $this->computeSunHours($lat, $i + 1),
                't2m_avg'   => (float) $params['T2M'][$key],
                't2m_max'   => (float) $params['T2M_MAX'][$key],
                't2m_min'   => (float) $params['T2M_MIN'][$key],
                'ws10m'     => (float) $params['WS10M'][$key],
            ];
        }

        return $monthly;
    }

    /**
     * Computes astronomical daylight hours for a given latitude and month.
     *
     * Uses the mid-month representative day (Cooper's formula for solar
     * declination) and the sunset hour angle to derive day length.
     */
    private function computeSunHours(float $lat, int $month): float
    {
        // Representative day-of-year for mid-month (Klein, 1977)
        $midMonthDoy = [17, 47, 75, 105, 135, 162, 198, 228, 258, 288, 318, 344];
        $n = $midMonthDoy[$month - 1];

        // Solar declination (Cooper's equation) in radians
        $decl = deg2rad(23.45 * sin(deg2rad(360.0 / 365.0 * (284 + $n))));
        $latRad = deg2rad($lat);

        // Sunset hour angle
        $cosWs = -tan($latRad) * tan($decl);

        // Handle polar day / polar night
        if ($cosWs < -1.0) {
            return 24.0; // Midnight sun
        }
        if ($cosWs > 1.0) {
            return 0.0;  // Polar night
        }

        $ws = acos($cosWs); // radians
        return round((2.0 * rad2deg($ws)) / 15.0, 2);
    }

    /**
     * Persists a new location and its 12 monthly rows inside a transaction.
     *
     * @param array<int, array<string, float>> $monthly
     * @throws PDOException
     */
    private function persist(
        float $lat,
        float $lng,
        float $absTmin,
        float $absTmax,
        array $monthly
    ): void {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO climatology_locations
                    (latitude, longitude, absolute_min_temp, absolute_max_temp, data_source)
                 VALUES (:lat, :lng, :tmin, :tmax, "NASA POWER")'
            );
            $stmt->execute([
                ':lat'  => $lat,
                ':lng'  => $lng,
                ':tmin' => $absTmin,
                ':tmax' => $absTmax,
            ]);
            $locationId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO climatology_monthly
                    (location_id, month, ghi_kwh_m2_day, dni_kwh_m2_day, dhi_kwh_m2_day, sun_hours, t2m_avg, t2m_max, t2m_min, ws10m)
                 VALUES (:loc, :month, :ghi, :dni, :dhi, :sun, :avg, :max, :min, :ws10m)'
            );
            foreach ($monthly as $row) {
                $stmt->execute([
                    ':loc'    => $locationId,
                    ':month'  => $row['month'],
                    ':ghi'    => $row['ghi'],
                    ':dni'    => $row['dni'],
                    ':dhi'    => $row['dhi'],
                    ':sun'    => $row['sun_hours'],
                    ':avg'    => $row['t2m_avg'],
                    ':max'    => $row['t2m_max'],
                    ':min'    => $row['t2m_min'],
                    ':ws10m'  => $row['ws10m'],
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
