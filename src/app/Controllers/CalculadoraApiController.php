<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\InverterRepository;
use App\Repositories\PVModuleRepository;
use App\Services\NasaService;
use RuntimeException;

class CalculadoraApiController
{
    public function __construct(
        private readonly NasaService         $nasa,
        private readonly PVModuleRepository  $modules,
        private readonly InverterRepository  $inverters,
    ) {}

    // ── Climate data ──────────────────────────────────────────────────────────

    /**
     * Validates coordinates and delegates to NasaService.
     *
     * @param  array<string, mixed> $body  Decoded POST body
     * @return array<string, mixed>
     */
    public function getClimateData(array $body): array
    {
        $lat = isset($body['lat']) ? (float) $body['lat'] : null;
        $lng = isset($body['lng']) ? (float) $body['lng'] : null;

        if (
            $lat === null || $lng === null
            || $lat < -90  || $lat > 90
            || $lng < -180 || $lng > 180
        ) {
            return ['error' => 'Coordenadas inválidas.', '__status' => 400];
        }

        try {
            return $this->nasa->getClimateData($lat, $lng);
        } catch (RuntimeException $e) {
            return ['error' => $e->getMessage(), '__status' => 502];
        }
    }

    // ── Equipment lists ───────────────────────────────────────────────────────

    /**
     * Returns all PV modules for the calculator card grid (no pagination).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPVModules(): array
    {
        return array_map(
            fn($m) => $m->toArray(),
            $this->modules->findAllForCalculator()
        );
    }

    /**
     * Returns all inverters for the calculator card grid (no pagination).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInverters(): array
    {
        return array_map(
            fn($i) => $i->toArray(),
            $this->inverters->findAllForCalculator()
        );
    }
}
