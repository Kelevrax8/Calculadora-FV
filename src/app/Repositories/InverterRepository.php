<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Inverter;
use App\Models\MpptGroup;
use PDO;

class InverterRepository
{
    public function __construct(private readonly PDO $pdo) {}

    // ── Query helpers ─────────────────────────────────────────────────────────

    /**
     * Returns the SELECT column list shared by all inverter queries.
     * No MPPT-specific columns — those live in inverter_mppt_groups.
     */
    private function inverterColumns(): string
    {
        return "i.id, mf.name AS manufacturer, i.manufacturer_id, i.model,
                i.pmax_dc_input, i.max_dc_voltage,
                i.mppt_voltage_min, i.mppt_voltage_max, i.startup_voltage,
                i.nominal_ac_power, i.ac_voltage_nominal,
                i.phase_type, i.efficiency_weighted,
                DATE_FORMAT(i.created_at, '%d/%m/%Y') AS created_at";
    }

    /**
     * Loads all MPPT groups for a set of inverter IDs and returns them indexed
     * by inverter_id.  Uses a single query regardless of how many inverters
     * are in the set.
     *
     * @param  int[]                  $ids
     * @return array<int, MpptGroup[]>
     */
    private function loadGroupsForInverters(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, inverter_id, group_label, mppt_count,
                    max_strings_per_mppt, max_input_current, max_short_circuit_current
             FROM inverter_mppt_groups
             WHERE inverter_id IN ($placeholders)
             ORDER BY inverter_id, id"
        );
        $stmt->execute($ids);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['inverter_id']][] = MpptGroup::fromArray($row);
        }
        return $result;
    }

    /**
     * Replaces all MPPT groups for the given inverter inside an already-open
     * transaction.  Delete-then-insert keeps things simple and correct.
     *
     * @param int        $inverterId
     * @param MpptGroup[] $groups
     */
    private function replaceGroups(int $inverterId, array $groups): void
    {
        $del = $this->pdo->prepare(
            'DELETE FROM inverter_mppt_groups WHERE inverter_id = :id'
        );
        $del->execute([':id' => $inverterId]);

        $ins = $this->pdo->prepare(
            'INSERT INTO inverter_mppt_groups
                 (inverter_id, group_label, mppt_count, max_strings_per_mppt,
                  max_input_current, max_short_circuit_current)
             VALUES
                 (:inverter_id, :group_label, :mppt_count, :max_strings_per_mppt,
                  :max_input_current, :max_short_circuit_current)'
        );

        foreach ($groups as $group) {
            $ins->execute([
                ':inverter_id'              => $inverterId,
                ':group_label'              => $group->label,
                ':mppt_count'               => $group->mpptCount,
                ':max_strings_per_mppt'     => $group->maxStringsPerMppt,
                ':max_input_current'        => $group->maxInputCurrent,
                ':max_short_circuit_current'=> $group->maxShortCircuitCurrent,
            ]);
        }
    }

    // ── Public API ────────────────────────────────────────────────────────────

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
     * Paginated list of inverters matching the search term, with groups loaded.
     *
     * @return Inverter[]
     */
    public function findAll(int $limit, int $offset, string $q = ''): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->inverterColumns()}
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

        $rows      = $stmt->fetchAll();
        $ids       = array_map(fn($r) => (int)$r['id'], $rows);
        $groupsMap = $this->loadGroupsForInverters($ids);

        return array_map(
            fn($row) => Inverter::fromArray($row, $groupsMap[(int)$row['id']] ?? []),
            $rows
        );
    }

    /**
     * All inverters, unpaginated, ordered for the calculator's card grid.
     * Groups are eager-loaded in a single additional query.
     *
     * @return Inverter[]
     */
    public function findAllForCalculator(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->inverterColumns()}
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             ORDER BY mf.name, i.nominal_ac_power"
        );
        $stmt->execute();

        $rows      = $stmt->fetchAll();
        $ids       = array_map(fn($r) => (int)$r['id'], $rows);
        $groupsMap = $this->loadGroupsForInverters($ids);

        return array_map(
            fn($row) => Inverter::fromArray($row, $groupsMap[(int)$row['id']] ?? []),
            $rows
        );
    }

    /**
     * Find a single inverter by primary key, with its groups.
     */
    public function findById(int $id): ?Inverter
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->inverterColumns()}
             FROM inverters i
             JOIN manufacturers mf ON i.manufacturer_id = mf.id
             WHERE i.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $groupsMap = $this->loadGroupsForInverters([(int)$row['id']]);
        return Inverter::fromArray($row, $groupsMap[(int)$row['id']] ?? []);
    }

    /**
     * Insert a new inverter or update an existing one (id > 0 = update).
     * Runs inside a transaction so the inverter row and its MPPT groups are
     * always written atomically.
     */
    public function save(Inverter $inverter): void
    {
        $this->pdo->beginTransaction();

        try {
            if ($inverter->id > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE inverters
                     SET manufacturer_id    = :manufacturer_id,
                         model              = :model,
                         pmax_dc_input      = :pmax_dc_input,
                         max_dc_voltage     = :max_dc_voltage,
                         mppt_voltage_min   = :mppt_voltage_min,
                         mppt_voltage_max   = :mppt_voltage_max,
                         startup_voltage    = :startup_voltage,
                         nominal_ac_power   = :nominal_ac_power,
                         ac_voltage_nominal = :ac_voltage_nominal,
                         phase_type         = :phase_type,
                         efficiency_weighted= :efficiency_weighted
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', $inverter->id, PDO::PARAM_INT);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO inverters
                         (manufacturer_id, model, pmax_dc_input, max_dc_voltage,
                          mppt_voltage_min, mppt_voltage_max, startup_voltage,
                          nominal_ac_power, ac_voltage_nominal, phase_type,
                          efficiency_weighted)
                     VALUES
                         (:manufacturer_id, :model, :pmax_dc_input, :max_dc_voltage,
                          :mppt_voltage_min, :mppt_voltage_max, :startup_voltage,
                          :nominal_ac_power, :ac_voltage_nominal, :phase_type,
                          :efficiency_weighted)'
                );
            }

            $stmt->bindValue(':manufacturer_id',     $inverter->manufacturerId, PDO::PARAM_INT);
            $stmt->bindValue(':model',               $inverter->model);
            $stmt->bindValue(':pmax_dc_input',       $inverter->pmaxDcInput);
            $stmt->bindValue(':max_dc_voltage',      $inverter->maxDcVoltage);
            $stmt->bindValue(':mppt_voltage_min',    $inverter->mpptVoltageMin);
            $stmt->bindValue(':mppt_voltage_max',    $inverter->mpptVoltageMax);
            $stmt->bindValue(':startup_voltage',     $inverter->startupVoltage);
            $stmt->bindValue(':nominal_ac_power',    $inverter->nominalAcPower);
            $stmt->bindValue(':ac_voltage_nominal',  $inverter->acVoltageNominal);
            $stmt->bindValue(':phase_type',          $inverter->phaseType);
            $stmt->bindValue(':efficiency_weighted', $inverter->efficiencyWeighted);
            $stmt->execute();

            $inverterId = $inverter->id > 0
                ? $inverter->id
                : (int)$this->pdo->lastInsertId();

            $this->replaceGroups($inverterId, $inverter->mpptGroups);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete an inverter by primary key.
     * Child groups are removed automatically via ON DELETE CASCADE.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM inverters WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}


