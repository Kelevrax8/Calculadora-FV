<?php

declare(strict_types=1);

/**
 * InverterRepositoryTest
 *
 * Unit tests for `App\Repositories\InverterRepository` using SQLite in-memory.
 * Covered methods:
 *  - `findById(int): ?Inverter`
 *  - `save(Inverter): void` (insert path)
 *
 * Use these tests in the thesis to show repository integration tests that run
 * against a lightweight SQLite database to validate SQL and mapping logic.
 */
namespace Tests\Unit\Repositories;

use App\Models\Inverter;
use App\Repositories\InverterRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class InverterRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE manufacturers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');

        $this->pdo->exec(
            'CREATE TABLE inverters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                manufacturer_id INTEGER NOT NULL,
                model TEXT NOT NULL,
                pmax_dc_input REAL NOT NULL,
                max_dc_voltage REAL NOT NULL,
                mppt_voltage_min REAL NOT NULL,
                mppt_voltage_max REAL NOT NULL,
                startup_voltage REAL NOT NULL,
                max_input_current_per_mppt REAL NOT NULL,
                max_short_circuit_current REAL NOT NULL,
                nominal_ac_power REAL NOT NULL,
                ac_voltage_nominal REAL NOT NULL,
                phase_type TEXT NOT NULL,
                efficiency_weighted REAL NOT NULL,
                mppt_count INTEGER NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->pdo->exec("INSERT INTO manufacturers (name) VALUES ('InvMaker')");

        $this->pdo->exec("INSERT INTO inverters (manufacturer_id, model, pmax_dc_input, max_dc_voltage, mppt_voltage_min, mppt_voltage_max, startup_voltage, max_input_current_per_mppt, max_short_circuit_current, nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted, mppt_count, created_at)
            VALUES (1, 'INV-1', 500.0, 600.0, 200.0, 450.0, 150.0, 12.3, 20.0, 480.0, 230.0, 'Three Phase', 98.5, 2, '2024-01-01 00:00:00')");
    }

    public function testFindByIdReturnsInverter(): void
    {
        $repo = new InverterRepository($this->pdo);
        $inv = $repo->findById(1);

        $this->assertInstanceOf(Inverter::class, $inv);
        $this->assertSame('INV-1', $inv->model);
    }

    public function testSaveInsertsNewInverter(): void
    {
        $repo = new InverterRepository($this->pdo);

        $new = new Inverter(
            id: 0,
            manufacturerId: 1,
            manufacturer: 'InvMaker',
            model: 'NEW-INV',
            pmaxDcInput: 600.0,
            maxDcVoltage: 650.0,
            mpptVoltageMin: 220.0,
            mpptVoltageMax: 500.0,
            startupVoltage: 160.0,
            maxInputCurrentPerMppt: 14.0,
            maxShortCircuitCurrent: 25.0,
            nominalAcPower: 500.0,
            acVoltageNominal: 230.0,
            phaseType: 'Three Phase',
            efficiencyWeighted: 98.0,
            mpptCount: 2,
            createdAt: ''
        );

        $repo->save($new);

        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM inverters WHERE model = 'NEW-INV'");
        $row = $stmt->fetch();

        $this->assertSame(1, (int)$row['cnt']);
    }
}
