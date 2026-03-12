<?php

declare(strict_types=1);

/**
 * ManufacturerRepositoryTest
 *
 * Unit tests for `App\Repositories\ManufacturerRepository` using SQLite in-memory.
 * Covered methods:
 *  - `findById(int): ?Manufacturer`
 *  - `save(string, int = 0): void` (insert/update; duplicate-name exception)
 *
 * These tests demonstrate verifying uniqueness constraints and basic CRUD
 * behaviour at the repository layer without a live MySQL server.
 */
namespace Tests\Unit\Repositories;

use App\Repositories\ManufacturerRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ManufacturerRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE manufacturers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $this->pdo->exec("INSERT INTO manufacturers (name, created_at) VALUES ('Alpha', '2024-01-01 00:00:00')");
    }

    public function testFindByIdReturnsManufacturer(): void
    {
        $repo = new ManufacturerRepository($this->pdo);
        $m = $repo->findById(1);

        $this->assertNotNull($m);
        $this->assertSame('Alpha', $m->name);
    }

    public function testSaveInsertsNewManufacturer(): void
    {
        $repo = new ManufacturerRepository($this->pdo);
        $repo->save('Beta');

        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM manufacturers WHERE name = 'Beta'");
        $row = $stmt->fetch();

        $this->assertSame(1, (int)$row['cnt']);
    }

    public function testSaveDuplicateThrows(): void
    {
        $this->expectException(\PDOException::class);
        $repo = new ManufacturerRepository($this->pdo);
        // 'Alpha' already exists
        $repo->save('Alpha');
    }
}
