<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Manufacturer;
use PDO;

class ManufacturerRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Total count of manufacturers matching the search term (for pagination).
     */
    public function count(string $q = ''): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM manufacturers WHERE name LIKE :q'
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of manufacturers matching the search term.
     *
     * @return Manufacturer[]
     */
    public function findAll(int $limit, int $offset, string $q = ''): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, strftime('%d/%m/%Y', created_at) AS created_at /* SQLite-compatible */
             FROM manufacturers
             WHERE name LIKE :q
             ORDER BY name
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':q',      '%' . $q . '%');
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => Manufacturer::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * All manufacturers, unpaginated, for populating select dropdowns.
     *
     * @return Manufacturer[]
     */
    public function findAllForSelect(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name FROM manufacturers ORDER BY name'
        );
        $stmt->execute();

        return array_map(
            fn(array $row) => Manufacturer::fromArray($row),
            $stmt->fetchAll()
        );
    }

    /**
     * Find a single manufacturer by primary key.
     */
    public function findById(int $id): ?Manufacturer
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, strftime('%d/%m/%Y', created_at) AS created_at /* SQLite-compatible */
             FROM manufacturers
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? Manufacturer::fromArray($row) : null;
    }

    /**
     * Insert a new manufacturer or update an existing one (id > 0 = update).
     *
     * @throws \PDOException on duplicate name (SQLSTATE 23000)
     */
    public function save(string $name, int $id = 0): void
    {
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE manufacturers SET name = :name WHERE id = :id'
            );
            $stmt->execute([':name' => $name, ':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO manufacturers (name) VALUES (:name)'
            );
            $stmt->execute([':name' => $name]);
        }
    }

    /**
     * Delete a manufacturer by primary key.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM manufacturers WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }
}
