<?php

declare(strict_types=1);

namespace App\Models;

class MpptGroup
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $inverterId,
        public readonly string $label,
        /** Number of physical MPPT inputs that share these current ratings. */
        public readonly int    $mpptCount,
        /** Maximum parallel strings the hardware allows per single MPPT input.
         *  NULL = no hard hardware limit; effective capacity is derived from current ratings.
         */
        public readonly ?int   $maxStringsPerMppt,
        /** Maximum continuous input current per MPPT input (Idc max). */
        public readonly float  $maxInputCurrent,
        /** Maximum short-circuit current per MPPT input (Isc max). */
        public readonly float  $maxShortCircuitCurrent,
    ) {}

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): static
    {
        return new static(
            id:                     (int)$row['id'],
            inverterId:             (int)$row['inverter_id'],
            label:                  (string)$row['group_label'],
            mpptCount:              (int)$row['mppt_count'],
            maxStringsPerMppt:      isset($row['max_strings_per_mppt']) && $row['max_strings_per_mppt'] !== null
                                        ? (int)$row['max_strings_per_mppt']
                                        : null,
            maxInputCurrent:        (float)$row['max_input_current'],
            maxShortCircuitCurrent: (float)$row['max_short_circuit_current'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                       => $this->id,
            'inverter_id'              => $this->inverterId,
            'group_label'              => $this->label,
            'mppt_count'               => $this->mpptCount,
            'max_strings_per_mppt'     => $this->maxStringsPerMppt,
            'max_input_current'        => $this->maxInputCurrent,
            'max_short_circuit_current'=> $this->maxShortCircuitCurrent,
        ];
    }
}
