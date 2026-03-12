<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Inverter;
use PDO;

class InverterRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Total count of inverters matching the search term (for pagination).
     */
    public function count(string $q = ''): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             WHERE i.model LIKE :q1 OR mf.name LIKE :q2'
        );
        $stmt->execute([':q1' => '%' . $q . '%', ':q2' => '%' . $q . '%']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of inverters matching the search term.
     *
     * @return Inverter[]
     */
    public function findAll(int $limit, int $offset, string $q = ''): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.id, mf.name AS manufacturer, i.manufacturer_id, i.model,
                    i.pmax_dc_input, i.max_dc_voltage, i.mppt_voltage_min, i.mppt_voltage_max,
                    i.startup_voltage, i.max_input_current_per_mppt, i.max_short_circuit_current,
                    i.nominal_ac_power, i.ac_voltage_nominal, i.phase_type, i.efficiency_weighted,
                    i.mppt_count,
                    strftime('%d/%m/%Y', i.created_at) AS created_at /* SQLite-compatible */
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             WHERE i.model LIKE :q1 OR mf.name LIKE :q2
             ORDER BY mf.name, i.model
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':q1',     '%' . $q . '%');
        $stmt->bindValue(':q2',     '%' . $q . '%');
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => Inverter::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * All inverters, unpaginated, ordered for the calculator's card grid.
     *
     * @return Inverter[]
     */
    public function findAllForCalculator(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.id, mf.name AS manufacturer, i.manufacturer_id, i.model,
                    i.pmax_dc_input, i.max_dc_voltage, i.mppt_voltage_min, i.mppt_voltage_max,
                    i.startup_voltage, i.max_input_current_per_mppt, i.max_short_circuit_current,
                    i.nominal_ac_power, i.ac_voltage_nominal, i.phase_type, i.efficiency_weighted,
                    i.mppt_count,
                    strftime('%d/%m/%Y', i.created_at) AS created_at /* SQLite-compatible */
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             ORDER BY mf.name, i.nominal_ac_power"
        );
        $stmt->execute();

        return array_map(
            fn(array $row) => Inverter::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * Find a single inverter by primary key.
     */
    public function findById(int $id): ?Inverter
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.id, mf.name AS manufacturer, i.manufacturer_id, i.model,
                    i.pmax_dc_input, i.max_dc_voltage, i.mppt_voltage_min, i.mppt_voltage_max,
                    i.startup_voltage, i.max_input_current_per_mppt, i.max_short_circuit_current,
                    i.nominal_ac_power, i.ac_voltage_nominal, i.phase_type, i.efficiency_weighted,
                    i.mppt_count,
                    strftime('%d/%m/%Y', i.created_at) AS created_at /* SQLite-compatible */
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             WHERE i.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? Inverter::fromArray($row) : null;
    }

    /**
     * Insert a new inverter or update an existing one (id > 0 = update).
     */
    public function save(Inverter $inverter): void
    {
        if ($inverter->id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE inverters
                 SET manufacturer_id              = :manufacturer_id,
                     model                        = :model,
                     pmax_dc_input                = :pmax_dc_input,
                     max_dc_voltage               = :max_dc_voltage,
                     mppt_voltage_min             = :mppt_voltage_min,
                     mppt_voltage_max             = :mppt_voltage_max,
                     startup_voltage              = :startup_voltage,
                     max_input_current_per_mppt   = :max_input_current_per_mppt,
                     max_short_circuit_current    = :max_short_circuit_current,
                     nominal_ac_power             = :nominal_ac_power,
                     ac_voltage_nominal           = :ac_voltage_nominal,
                     phase_type                   = :phase_type,
                     efficiency_weighted          = :efficiency_weighted,
                     mppt_count                   = :mppt_count
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $inverter->id, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO inverters
                     (manufacturer_id, model, pmax_dc_input, max_dc_voltage,
                      mppt_voltage_min, mppt_voltage_max, startup_voltage,
                      max_input_current_per_mppt, max_short_circuit_current,
                      nominal_ac_power, ac_voltage_nominal, phase_type,
                      efficiency_weighted, mppt_count)
                 VALUES
                     (:manufacturer_id, :model, :pmax_dc_input, :max_dc_voltage,
                      :mppt_voltage_min, :mppt_voltage_max, :startup_voltage,
                      :max_input_current_per_mppt, :max_short_circuit_current,
                      :nominal_ac_power, :ac_voltage_nominal, :phase_type,
                      :efficiency_weighted, :mppt_count)'
            );
        }

        $stmt->bindValue(':manufacturer_id',            $inverter->manufacturerId,        PDO::PARAM_INT);
        $stmt->bindValue(':model',                      $inverter->model);
        $stmt->bindValue(':pmax_dc_input',              $inverter->pmaxDcInput);
        $stmt->bindValue(':max_dc_voltage',             $inverter->maxDcVoltage);
        $stmt->bindValue(':mppt_voltage_min',           $inverter->mpptVoltageMin);
        $stmt->bindValue(':mppt_voltage_max',           $inverter->mpptVoltageMax);
        $stmt->bindValue(':startup_voltage',            $inverter->startupVoltage);
        $stmt->bindValue(':max_input_current_per_mppt', $inverter->maxInputCurrentPerMppt);
        $stmt->bindValue(':max_short_circuit_current',  $inverter->maxShortCircuitCurrent);
        $stmt->bindValue(':nominal_ac_power',           $inverter->nominalAcPower);
        $stmt->bindValue(':ac_voltage_nominal',         $inverter->acVoltageNominal);
        $stmt->bindValue(':phase_type',                 $inverter->phaseType);
        $stmt->bindValue(':efficiency_weighted',        $inverter->efficiencyWeighted);
        $stmt->bindValue(':mppt_count',                 $inverter->mpptCount, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Delete an inverter by primary key.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM inverters WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
