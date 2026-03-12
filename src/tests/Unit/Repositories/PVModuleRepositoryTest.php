<?php

declare(strict_types=1);

/**
 * PVModuleRepositoryTest
 *
 * Unit tests for `App\Repositories\PVModuleRepository` using SQLite in-memory.
 * Covered methods:
 *  - `findById(int): ?PVModule`
 *  - `save(PVModule): void` (insert path)
 *
 * Usage: include the test class in the thesis's testing appendix as a runnable
 * example of repository-level integration tests that execute real SQL
 * against an in-memory SQLite database.
 */
namespace Tests\Unit\Repositories;

use App\Models\PVModule;
use App\Repositories\PVModuleRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PVModuleRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Minimal schema needed for PVModuleRepository
        $this->pdo->exec('CREATE TABLE manufacturers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');

        $this->pdo->exec(
            'CREATE TABLE pv_modules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                manufacturer_id INTEGER NOT NULL,
                model TEXT NOT NULL,
                technology TEXT NOT NULL,
                pmax_stc REAL NOT NULL,
                voc_stc REAL NOT NULL,
                isc_stc REAL NOT NULL,
                vmpp_stc REAL NOT NULL,
                imp_stc REAL NOT NULL,
                temp_coeff_voc REAL NOT NULL,
                temp_coeff_pmax REAL NOT NULL,
                length_m REAL NOT NULL,
                width_m REAL NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        // Seed a manufacturer and a module
        $this->pdo->exec("INSERT INTO manufacturers (name, created_at) VALUES ('SunTest', '2024-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO pv_modules (manufacturer_id, model, technology, pmax_stc, voc_stc, isc_stc, vmpp_stc, imp_stc, temp_coeff_voc, temp_coeff_pmax, length_m, width_m, created_at)
            VALUES (1, 'TEST-100', 'Monocrystalline', 100.0, 40.0, 3.0, 33.0, 3.03, -0.1234, -0.4567, 1.6, 1.0, '2024-01-01 00:00:00')");
    }

    public function testFindByIdReturnsModule(): void
    {
        $repo = new PVModuleRepository($this->pdo);
        $module = $repo->findById(1);

        $this->assertInstanceOf(PVModule::class, $module);
        $this->assertSame('TEST-100', $module->model);
        $this->assertSame(1, $module->manufacturerId);
        $this->assertSame('SunTest', $module->manufacturer);
        $this->assertStringMatchesFormat('%d/%d/%d', $module->createdAt);
    }

    public function testSaveInsertsNewModule(): void
    {
        $repo = new PVModuleRepository($this->pdo);

        $new = new PVModule(
            id: 0,
            manufacturerId: 1,
            manufacturer: 'SunTest',
            model: 'INSERT-1',
            technology: 'Monocrystalline',
            pmaxStc: 120.0,
            vocStc: 45.0,
            iscStc: 3.5,
            vmppStc: 36.0,
            impStc: 3.33,
            tempCoeffVoc: -0.1000,
            tempCoeffPmax: -0.4000,
            lengthM: 1.7,
            widthM: 1.0,
            createdAt: ''
        );

        $repo->save($new);

        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM pv_modules WHERE model = 'INSERT-1'");
        $row = $stmt->fetch();

        $this->assertSame(1, (int)$row['cnt']);
    }
}
