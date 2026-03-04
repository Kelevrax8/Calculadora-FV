<?php

declare(strict_types=1);

namespace App\Models;

class PVModule
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $manufacturerId,
        public readonly string $manufacturer,
        public readonly string $model,
        public readonly string $technology,
        public readonly float  $pmaxStc,
        public readonly float  $vocStc,
        public readonly float  $iscStc,
        public readonly float  $vmppStc,
        public readonly float  $impStc,
        public readonly float  $tempCoeffVoc,
        public readonly float  $tempCoeffPmax,
        public readonly float  $lengthM,
        public readonly float  $widthM,
        public readonly string $createdAt,
    ) {}

    /**
     * Hydrates a PVModule from a raw PDO associative row.
     * Expects a JOIN with manufacturers so the manufacturer name is included.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): static
    {
        return new static(
            id:             (int)$row['id'],
            manufacturerId: (int)$row['manufacturer_id'],
            manufacturer:   (string)($row['manufacturer'] ?? ''),
            model:          (string)$row['model'],
            technology:     (string)$row['technology'],
            pmaxStc:        (float)$row['pmax_stc'],
            vocStc:         (float)$row['voc_stc'],
            iscStc:         (float)$row['isc_stc'],
            vmppStc:        (float)$row['vmpp_stc'],
            impStc:         (float)$row['imp_stc'],
            tempCoeffVoc:   (float)$row['temp_coeff_voc'],
            tempCoeffPmax:  (float)$row['temp_coeff_pmax'],
            lengthM:        (float)$row['length_m'],
            widthM:         (float)$row['width_m'],
            createdAt:      (string)($row['created_at'] ?? ''),
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
            'id'              => $this->id,
            'manufacturer_id' => $this->manufacturerId,
            'manufacturer'    => $this->manufacturer,
            'model'           => $this->model,
            'technology'      => $this->technology,
            'pmax_stc'        => $this->pmaxStc,
            'voc_stc'         => $this->vocStc,
            'isc_stc'         => $this->iscStc,
            'vmpp_stc'        => $this->vmppStc,
            'imp_stc'         => $this->impStc,
            'temp_coeff_voc'  => $this->tempCoeffVoc,
            'temp_coeff_pmax' => $this->tempCoeffPmax,
            'length_m'        => $this->lengthM,
            'width_m'         => $this->widthM,
            'created_at'      => $this->createdAt,
        ];
    }
}
