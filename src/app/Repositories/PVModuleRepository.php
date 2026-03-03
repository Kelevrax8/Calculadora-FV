<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PVModule;
use PDO;

class PVModuleRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Total count of PV modules matching the search term (for pagination).
     */
    public function count(string $q = ''): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM pv_modules m
             JOIN manufacturers mf ON m.manufacturer_id = mf.id
             WHERE m.model LIKE :q1 OR mf.name LIKE :q2'
        );
        $stmt->execute([':q1' => '%' . $q . '%', ':q2' => '%' . $q . '%']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of PV modules matching the search term.
     *
     * @return PVModule[]
     */
    public function findAll(int $limit, int $offset, string $q = ''): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, mf.name AS manufacturer, m.manufacturer_id, m.model,
                    m.technology, m.pmax_stc, m.voc_stc, m.isc_stc, m.vmpp_stc, m.imp_stc,
                    m.temp_coeff_voc, m.temp_coeff_pmax, m.length_m, m.width_m,
                    DATE_FORMAT(m.created_at, '%d/%m/%Y') AS created_at
             FROM pv_modules m
             JOIN manufacturers mf ON m.manufacturer_id = mf.id
             WHERE m.model LIKE :q1 OR mf.name LIKE :q2
             ORDER BY mf.name, m.model
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':q1',     '%' . $q . '%');
        $stmt->bindValue(':q2',     '%' . $q . '%');
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => PVModule::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * All PV modules, unpaginated, ordered for the calculator's card grid.
     *
     * @return PVModule[]
     */
    public function findAllForCalculator(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, mf.name AS manufacturer, m.manufacturer_id, m.model,
                    m.technology, m.pmax_stc, m.voc_stc, m.isc_stc, m.vmpp_stc, m.imp_stc,
                    m.temp_coeff_voc, m.temp_coeff_pmax, m.length_m, m.width_m,
                    DATE_FORMAT(m.created_at, '%d/%m/%Y') AS created_at
             FROM pv_modules m
             JOIN manufacturers mf ON m.manufacturer_id = mf.id
             ORDER BY mf.name, m.pmax_stc"
        );
        $stmt->execute();

        return array_map(
            fn(array $row) => PVModule::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * Find a single PV module by primary key.
     */
    public function findById(int $id): ?PVModule
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, mf.name AS manufacturer, m.manufacturer_id, m.model,
                    m.technology, m.pmax_stc, m.voc_stc, m.isc_stc, m.vmpp_stc, m.imp_stc,
                    m.temp_coeff_voc, m.temp_coeff_pmax, m.length_m, m.width_m,
                    DATE_FORMAT(m.created_at, '%d/%m/%Y') AS created_at
             FROM pv_modules m
             JOIN manufacturers mf ON m.manufacturer_id = mf.id
             WHERE m.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? PVModule::fromArray($row) : null;
    }

    /**
     * Insert a new PV module or update an existing one (id > 0 = update).
     *
     * @throws \InvalidArgumentException if temperature coefficients are not negative
     */
    public function save(PVModule $module): void
    {
        if ($module->tempCoeffVoc >= 0) {
            throw new \InvalidArgumentException(
                'El coeficiente de temperatura de Voc (β Voc) debe ser negativo.'
            );
        }
        if ($module->tempCoeffPmax >= 0) {
            throw new \InvalidArgumentException(
                'El coeficiente de temperatura de Pmax (β Pmax) debe ser negativo.'
            );
        }

        if ($module->id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE pv_modules
                 SET manufacturer_id  = :manufacturer_id,
                     model            = :model,
                     technology       = :technology,
                     pmax_stc         = :pmax_stc,
                     voc_stc          = :voc_stc,
                     isc_stc          = :isc_stc,
                     vmpp_stc         = :vmpp_stc,
                     imp_stc          = :imp_stc,
                     temp_coeff_voc   = :temp_coeff_voc,
                     temp_coeff_pmax  = :temp_coeff_pmax,
                     length_m         = :length_m,
                     width_m          = :width_m
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $module->id, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pv_modules
                     (manufacturer_id, model, technology, pmax_stc, voc_stc, isc_stc,
                      vmpp_stc, imp_stc, temp_coeff_voc, temp_coeff_pmax, length_m, width_m)
                 VALUES
                     (:manufacturer_id, :model, :technology, :pmax_stc, :voc_stc, :isc_stc,
                      :vmpp_stc, :imp_stc, :temp_coeff_voc, :temp_coeff_pmax, :length_m, :width_m)'
            );
        }

        $stmt->bindValue(':manufacturer_id', $module->manufacturerId, PDO::PARAM_INT);
        $stmt->bindValue(':model',           $module->model);
        $stmt->bindValue(':technology',      $module->technology);
        $stmt->bindValue(':pmax_stc',        $module->pmaxStc);
        $stmt->bindValue(':voc_stc',         $module->vocStc);
        $stmt->bindValue(':isc_stc',         $module->iscStc);
        $stmt->bindValue(':vmpp_stc',        $module->vmppStc);
        $stmt->bindValue(':imp_stc',         $module->impStc);
        $stmt->bindValue(':temp_coeff_voc',  $module->tempCoeffVoc);
        $stmt->bindValue(':temp_coeff_pmax', $module->tempCoeffPmax);
        $stmt->bindValue(':length_m',        $module->lengthM);
        $stmt->bindValue(':width_m',         $module->widthM);
        $stmt->execute();
    }

    /**
     * Delete a PV module by primary key.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pv_modules WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
