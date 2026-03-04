<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Inverter;
use App\Models\PVModule;
use App\Repositories\InverterRepository;
use App\Repositories\ManufacturerRepository;
use App\Repositories\PVModuleRepository;
use InvalidArgumentException;
use PDOException;

class InventarioApiController
{
    private const PAGE_SIZE = 5;

    public function __construct(
        private readonly ManufacturerRepository $manufacturers,
        private readonly PVModuleRepository     $modules,
        private readonly InverterRepository     $inverters,
    ) {}

    // ── Manufacturers ─────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $params  $_GET parameters
     * @return array<string, mixed>
     */
    public function listManufacturers(array $params): array
    {
        $page   = max(1, (int)($params['page'] ?? 1));
        $q      = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::PAGE_SIZE;

        return [
            'total' => $this->manufacturers->count($q),
            'data'  => array_map(
                fn($m) => $m->toArray(),
                $this->manufacturers->findAll(self::PAGE_SIZE, $offset, $q)
            ),
        ];
    }

    /**
     * @param array<string, mixed> $body  Decoded JSON request body
     * @return array<string, mixed>
     */
    public function saveManufacturer(array $body): array
    {
        $name = trim($body['name'] ?? '');
        $id   = (int)($body['id'] ?? 0);

        if ($name === '') {
            return ['error' => 'Nombre requerido'];
        }

        try {
            $this->manufacturers->save($name, $id);
            return ['ok' => true];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['error' => 'Este fabricante ya está registrado. Utiliza un nombre diferente.'];
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function deleteManufacturer(array $body): array
    {
        $id = (int)($body['id'] ?? 0);

        try {
            $this->manufacturers->delete($id);
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function manufacturersForSelect(): array
    {
        return array_map(
            fn($m) => $m->toArray(),
            $this->manufacturers->findAllForSelect()
        );
    }

    // ── PV Modules ────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listModules(array $params): array
    {
        $page   = max(1, (int)($params['page'] ?? 1));
        $q      = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::PAGE_SIZE;

        return [
            'total' => $this->modules->count($q),
            'data'  => array_map(
                fn($m) => $m->toArray(),
                $this->modules->findAll(self::PAGE_SIZE, $offset, $q)
            ),
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function saveModule(array $body): array
    {
        try {
            $module = new PVModule(
                id:             (int)($body['id']              ?? 0),
                manufacturerId: (int)($body['manufacturer_id'] ?? 0),
                manufacturer:   '',
                model:          trim($body['model']            ?? ''),
                technology:     $body['technology']            ?? '',
                pmaxStc:        (float)($body['pmax_stc']        ?? 0),
                vocStc:         (float)($body['voc_stc']         ?? 0),
                iscStc:         (float)($body['isc_stc']         ?? 0),
                vmppStc:        (float)($body['vmpp_stc']        ?? 0),
                impStc:         (float)($body['imp_stc']         ?? 0),
                tempCoeffVoc:   (float)($body['temp_coeff_voc']  ?? 0),
                tempCoeffPmax:  (float)($body['temp_coeff_pmax'] ?? 0),
                lengthM:        (float)($body['length_m']        ?? 0),
                widthM:         (float)($body['width_m']         ?? 0),
                createdAt:      '',
            );

            $this->modules->save($module);
            return ['ok' => true];
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function deleteModule(array $body): array
    {
        $id = (int)($body['id'] ?? 0);

        try {
            $this->modules->delete($id);
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ── Inverters ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listInverters(array $params): array
    {
        $page   = max(1, (int)($params['page'] ?? 1));
        $q      = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::PAGE_SIZE;

        return [
            'total' => $this->inverters->count($q),
            'data'  => array_map(
                fn($i) => $i->toArray(),
                $this->inverters->findAll(self::PAGE_SIZE, $offset, $q)
            ),
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function saveInverter(array $body): array
    {
        try {
            $inverter = new Inverter(
                id:                     (int)($body['id']                           ?? 0),
                manufacturerId:         (int)($body['manufacturer_id']              ?? 0),
                manufacturer:           '',
                model:                  trim($body['model']                         ?? ''),
                pmaxDcInput:            (float)($body['pmax_dc_input']              ?? 0),
                maxDcVoltage:           (float)($body['max_dc_voltage']             ?? 0),
                mpptVoltageMin:         (float)($body['mppt_voltage_min']           ?? 0),
                mpptVoltageMax:         (float)($body['mppt_voltage_max']           ?? 0),
                startupVoltage:         (float)($body['startup_voltage']            ?? 0),
                maxInputCurrentPerMppt: (float)($body['max_input_current_per_mppt'] ?? 0),
                maxShortCircuitCurrent: (float)($body['max_short_circuit_current']  ?? 0),
                nominalAcPower:         (float)($body['nominal_ac_power']           ?? 0),
                acVoltageNominal:       (float)($body['ac_voltage_nominal']         ?? 0),
                phaseType:              $body['phase_type']                         ?? '',
                efficiencyWeighted:     (float)($body['efficiency_weighted']        ?? 0),
                mpptCount:              (int)($body['mppt_count']                   ?? 0),
                createdAt:              '',
            );

            $this->inverters->save($inverter);
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function deleteInverter(array $body): array
    {
        $id = (int)($body['id'] ?? 0);

        try {
            $this->inverters->delete($id);
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
