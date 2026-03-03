<?php

declare(strict_types=1);

namespace App\Models;

class Inverter
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $manufacturerId,
        public readonly string $manufacturer,
        public readonly string $model,
        public readonly float  $pmaxDcInput,
        public readonly float  $maxDcVoltage,
        public readonly float  $mpptVoltageMin,
        public readonly float  $mpptVoltageMax,
        public readonly float  $startupVoltage,
        public readonly float  $maxInputCurrentPerMppt,
        public readonly float  $maxShortCircuitCurrent,
        public readonly float  $nominalAcPower,
        public readonly float  $acVoltageNominal,
        public readonly string $phaseType,
        public readonly float  $efficiencyWeighted,
        public readonly int    $mpptCount,
        public readonly string $createdAt,
    ) {}

    /**
     * Hydrates an Inverter from a raw PDO associative row.
     * Expects a JOIN with manufacturers so the manufacturer name is included.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): static
    {
        return new static(
            id:                     (int)$row['id'],
            manufacturerId:         (int)$row['manufacturer_id'],
            manufacturer:           (string)($row['manufacturer'] ?? ''),
            model:                  (string)$row['model'],
            pmaxDcInput:            (float)$row['pmax_dc_input'],
            maxDcVoltage:           (float)$row['max_dc_voltage'],
            mpptVoltageMin:         (float)$row['mppt_voltage_min'],
            mpptVoltageMax:         (float)$row['mppt_voltage_max'],
            startupVoltage:         (float)$row['startup_voltage'],
            maxInputCurrentPerMppt: (float)$row['max_input_current_per_mppt'],
            maxShortCircuitCurrent: (float)$row['max_short_circuit_current'],
            nominalAcPower:         (float)$row['nominal_ac_power'],
            acVoltageNominal:       (float)$row['ac_voltage_nominal'],
            phaseType:              (string)$row['phase_type'],
            efficiencyWeighted:     (float)$row['efficiency_weighted'],
            mpptCount:              (int)$row['mppt_count'],
            createdAt:              (string)($row['created_at'] ?? ''),
        );
    }

    /**
     * Serializes the model to a plain array for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                           => $this->id,
            'manufacturer_id'              => $this->manufacturerId,
            'manufacturer'                 => $this->manufacturer,
            'model'                        => $this->model,
            'pmax_dc_input'                => $this->pmaxDcInput,
            'max_dc_voltage'               => $this->maxDcVoltage,
            'mppt_voltage_min'             => $this->mpptVoltageMin,
            'mppt_voltage_max'             => $this->mpptVoltageMax,
            'startup_voltage'              => $this->startupVoltage,
            'max_input_current_per_mppt'   => $this->maxInputCurrentPerMppt,
            'max_short_circuit_current'    => $this->maxShortCircuitCurrent,
            'nominal_ac_power'             => $this->nominalAcPower,
            'ac_voltage_nominal'           => $this->acVoltageNominal,
            'phase_type'                   => $this->phaseType,
            'efficiency_weighted'          => $this->efficiencyWeighted,
            'mppt_count'                   => $this->mpptCount,
            'created_at'                   => $this->createdAt,
        ];
    }
}
