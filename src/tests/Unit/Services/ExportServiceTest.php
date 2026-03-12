<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ExportService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PHPUnit\Framework\TestCase;

final class ExportServiceTest extends TestCase
{
    public function testBuildCreatesWorkbookWithSheetsAndValues(): void
    {
        $svc = new ExportService();

        // Minimal payload with monthly data (12 months)
        $monthly = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthly[] = [
                'month' => $i,
                'ghi' => 5.0 + $i,
                'production' => 100 + $i * 10,
            ];
        }

        $payload = [
            'site' => ['lat' => 10.0, 'lng' => -20.0, 'consumo' => 5000, 'hsp' => 5.0, 'tmin' => 10, 'tmax' => 35],
            'module' => ['manufacturer' => 'Test', 'model' => 'MOD-1', 'pmax_stc' => 100, 'voc_stc' => 40, 'vmpp_stc' => 33, 'isc_stc' => 3.5, 'imp_stc' => 3.0, 'temp_coeff_voc' => -0.3, 'temp_coeff_pmax' => -0.4],
            'array' => ['Ns' => 10, 'Np' => 2, 'N' => 20, 'P_stc_kW' => 2.0, 'Voc_cold' => 400, 'Vmpp_hot' => 330, 'Vmpp_cold' => 300, 'arrArea' => 32.0],
            'inverter' => ['manufacturer' => 'Inv', 'model' => 'INV-1', 'nominal_ac_power' => 480000, 'phase_type' => 'Three Phase', 'ac_voltage_nominal' => 230, 'mppt_voltage_min' => 200, 'mppt_voltage_max' => 500, 'max_dc_voltage' => 600, 'max_input_current_per_mppt' => 20, 'max_short_circuit_current' => 40, 'mppt_count' => 2],
            'checks' => [['label' => 'chk1', 'detail' => 'd', 'pass' => true, 'hard' => false]],
            'energy' => ['E_year' => 12000, 'coverage' => 50, 'PR' => 0.75, 'dc_ac' => 1.2],
            'protection' => ['derating_on' => false, 'dc' => ['isc_module' => 3.5, 'I_design' => 5, 'OCPD' => '15A', 'AWG' => '14'], 'ac' => ['phase_type' => 'Three Phase', 'I_base' => 10, 'I_design' => 12, 'OCPD' => '20A', 'AWG' => '12']],
            'monthly' => $monthly,
        ];

        $xlsx = $svc->build($payload);
        // Write to temp file and load via PhpSpreadsheet reader
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $xlsx);

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($tmp);

        // Assert sheets
        $this->assertSame('Resumen', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Producción Mensual', $spreadsheet->getSheet(1)->getTitle());

        // Check some known cells
        $title = $spreadsheet->getSheet(0)->getCell('A1')->getValue();
        $this->assertStringContainsString('CALCULADORA FV', $title);

        $monthlyTitle = $spreadsheet->getSheet(1)->getCell('A1')->getValue();
        $this->assertSame('PRODUCCIÓN MENSUAL ESTIMADA', $monthlyTitle);

        // Row count: total rows should be at least 3 + 12 + total row
        $sheet2 = $spreadsheet->getSheet(1);
        $this->assertSame(16, $sheet2->getHighestRow()); // header + 12 months + totals + note rows

        // Cleanup
        @unlink($tmp);
    }
}
