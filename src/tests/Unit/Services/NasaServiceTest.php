<?php

declare(strict_types=1);

/**
 * NasaServiceTest
 *
 * Unit tests for `App\Services\NasaService` that verify:
 *  - returning cached climatology rows from the DB
 *  - persisting a new location and its monthly rows
 *
 * Notes for thesis:
 *  - `fetchFromNasa()` (external HTTP call) is not executed in these tests;
 *    network interactions are kept out of unit tests. Instead, cached and
 *    persistence behaviour are asserted using an in-memory SQLite DB.
 */
namespace Tests\Unit\Services;

use App\Services\NasaService;
use PDO;
use PHPUnit\Framework\TestCase;

final class NasaServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE climatology_locations (id INTEGER PRIMARY KEY AUTOINCREMENT, latitude REAL, longitude REAL, absolute_min_temp REAL, absolute_max_temp REAL, data_source TEXT, last_updated TEXT, UNIQUE(latitude, longitude))');

        $this->pdo->exec('CREATE TABLE climatology_monthly (id INTEGER PRIMARY KEY AUTOINCREMENT, location_id INTEGER, month INTEGER, ghi_kwh_m2_day REAL, t2m_avg REAL, t2m_max REAL, t2m_min REAL, UNIQUE(location_id, month))');
    }

    public function testReturnsCachedDataWhenPresent(): void
    {
        // Seed cached location and monthly
        $this->pdo->exec("INSERT INTO climatology_locations (latitude, longitude, absolute_min_temp, absolute_max_temp) VALUES (10.12, -20.34, -5.0, 40.0)");
        $locId = (int)$this->pdo->lastInsertId();

        for ($m = 1; $m <= 12; $m++) {
            $this->pdo->prepare('INSERT INTO climatology_monthly (location_id, month, ghi_kwh_m2_day, t2m_avg, t2m_max, t2m_min) VALUES (:loc, :month, :ghi, :avg, :max, :min)')
                ->execute([':loc' => $locId, ':month' => $m, ':ghi' => 5.0 + $m, ':avg' => 20.0, ':max' => 25.0, ':min' => 15.0]);
        }

        $svc = new NasaService($this->pdo);
        $result = $svc->getClimateData(10.12, -20.34);

        $this->assertSame('cache', $result['source']);
        $this->assertCount(12, $result['monthly']);
        $this->assertSame(-5.0, $result['tmin']);
        $this->assertSame(40.0, $result['tmax']);
    }

    public function testPersistCreatesLocationAndMonthly(): void
    {
        $svc = new NasaService($this->pdo);

        // Prepare synthetic monthly data
        $monthly = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthly[] = ['month' => $i, 'ghi' => 4.0 + $i, 't2m_avg' => 18.0, 't2m_max' => 22.0, 't2m_min' => 12.0];
        }

        // Use reflection to call private persist
        $ref = new \ReflectionClass(NasaService::class);
        $method = $ref->getMethod('persist');
        $method->setAccessible(true);

        $method->invokeArgs($svc, [9.87, -12.34, 12.0, 30.0, $monthly]);

        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM climatology_locations WHERE latitude = 9.87 AND longitude = -12.34');
        $row = $stmt->fetch();
        $this->assertSame(1, (int)$row['cnt']);

        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM climatology_monthly');
        $row = $stmt->fetch();
        $this->assertSame(12, (int)$row['cnt']);
    }
}
