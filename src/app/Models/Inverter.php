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
        /** Shared MPPT voltage window — the same for all MPPT inputs on this inverter. */
        public readonly float  $mpptVoltageMin,
        public readonly float  $mpptVoltageMax,
        public readonly float  $startupVoltage,
        public readonly float  $nominalAcPower,
        public readonly float  $acVoltageNominal,
        public readonly string $phaseType,
        public readonly float  $efficiencyWeighted,
        public readonly string $createdAt,
        /** @var MpptGroup[] One entry per group of identically-rated MPPT inputs. */
        public readonly array  $mpptGroups = [],
    ) {}

    // ── Derived helpers ───────────────────────────────────────────────────────

    /** Sum of mppt_count across all groups → total physical MPPT inputs. */
    public function totalMpptCount(): int
    {
        return (int) array_sum(array_map(fn(MpptGroup $g) => $g->mpptCount, $this->mpptGroups));
    }

    /**
     * Maximum total parallel strings this inverter can accept across all inputs:
     * sum of (mppt_count × max_strings_per_mppt) over all groups.
     */
    public function totalMaxStrings(): int
    {
        return (int) array_sum(
            array_map(fn(MpptGroup $g) => $g->mpptCount * $g->maxStringsPerMppt, $this->mpptGroups)
        );
    }

    // ── Hydration ─────────────────────────────────────────────────────────────

    /**
     * Hydrates an Inverter from a raw PDO associative row.
     * Expects a JOIN with manufacturers so the manufacturer name is included.
     *
     * @param array<string, mixed> $row
     * @param MpptGroup[]          $groups  Pre-loaded MPPT groups for this inverter.
     */
    public static function fromArray(array $row, array $groups = []): static
    {
        return new static(
            id:                 (int)$row['id'],
            manufacturerId:     (int)$row['manufacturer_id'],
            manufacturer:       (string)($row['manufacturer'] ?? ''),
            model:              (string)$row['model'],
            pmaxDcInput:        (float)$row['pmax_dc_input'],
            maxDcVoltage:       (float)$row['max_dc_voltage'],
            mpptVoltageMin:     (float)$row['mppt_voltage_min'],
            mpptVoltageMax:     (float)$row['mppt_voltage_max'],
            startupVoltage:     (float)$row['startup_voltage'],
            nominalAcPower:     (float)$row['nominal_ac_power'],
            acVoltageNominal:   (float)$row['ac_voltage_nominal'],
            phaseType:          (string)$row['phase_type'],
            efficiencyWeighted: (float)$row['efficiency_weighted'],
            createdAt:          (string)($row['created_at'] ?? ''),
            mpptGroups:         $groups,
        );
    }

    // ── Serialisation ─────────────────────────────────────────────────────────

    /**
     * Serializes the model to a plain array for JSON responses.
     *
     * Backward-compat note: the synthesised fields below keep the old JS
     * calculator working until Phase 3 updates calc-bloque3/4.js.
     *   mppt_count              → totalMpptCount()
     *   max_input_current_per_mppt  → first group's maxInputCurrent
     *   max_short_circuit_current   → first group's maxShortCircuitCurrent
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $firstGroup = $this->mpptGroups[0] ?? null;

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
            'nominal_ac_power'             => $this->nominalAcPower,
            'ac_voltage_nominal'           => $this->acVoltageNominal,
            'phase_type'                   => $this->phaseType,
            'efficiency_weighted'          => $this->efficiencyWeighted,
            'created_at'                   => $this->createdAt,

            // ── Backward-compat synthesised fields (convenience shorthand; still used
            //    in the JS card display as fallback for single-group inverters) ──────
            'mppt_count'                   => $this->totalMpptCount(),
            'max_input_current_per_mppt'   => $firstGroup?->maxInputCurrent ?? 0.0,
            'max_short_circuit_current'    => $firstGroup?->maxShortCircuitCurrent ?? 0.0,

            // ── New structured data ──────────────────────────────────────────
            'mppt_groups'                  => array_map(
                fn(MpptGroup $g) => $g->toArray(),
                $this->mpptGroups
            ),
        ];
    }
}

