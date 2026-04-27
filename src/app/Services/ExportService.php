<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ExportService — generates an .xlsx summary of a PV system design.
 *
 * Receives the decoded JSON payload from the browser (all values
 * already computed by calc-bloque4.js) and builds the workbook.
 */
class ExportService
{
    // ── Color palette (ARGB) ──────────────────────────────────
    private const C_TITLE_BG    = 'FF1D4ED8'; 
    private const C_TITLE_FG    = 'FFFFFFFF';
    private const C_SECTION_BG  = 'FFE5E7EB'; 
    private const C_SECTION_FG  = 'FF111827'; 
    private const C_SUBHDR_BG   = 'FFF3F4F6'; 
    private const C_SUBHDR_FG   = 'FF374151'; 
    private const C_ODD_BG      = 'FFF9FAFB'; 
    private const C_LABEL_FG    = 'FF6B7280'; 
    private const C_PASS_BG     = 'FFBBF7D0'; 
    private const C_PASS_FG     = 'FF15803D';
    private const C_FAIL_BG     = 'FFFECACA'; 
    private const C_FAIL_FG     = 'FFB91C1C';
    private const C_WARN_BG     = 'FFFEF08A';
    private const C_WARN_FG     = 'FFB45309';

    private int $row = 1;

    // ── Public entry point ────────────────────────────────────
    /**
     * @param  array<string, mixed> $payload  Decoded JSON from the browser
     * @return string                         Raw .xlsx binary content
     */
    public function build(array $payload): string
    {
        $this->row  = 1;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Calculadora FV')
            ->setTitle('Resumen Sistema Fotovoltaico')
            ->setDescription('Diseño preliminar generado por Calculadora FV');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');
        $this->setColumnWidths($sheet, [
            'A' => 42, 'B' => 45, 'C' => 14,
            'D' => 3,
            'E' => 16, 'F' => 16, 'G' => 16,
            'H' => 12, 'I' => 12, 'J' => 12,
            'K' => 10, 'L' => 22,
            'M' => 20, 'N' => 18, 'O' => 22,
        ]);

        $this->buildResumen($sheet, $payload);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
    }

    // ── Sheet 1 builder ───────────────────────────────────────
    private function buildResumen(Worksheet $s, array $p): void
    {
        $site    = $p['site']       ?? [];
        $mod     = $p['module']     ?? [];
        $arr     = $p['array']      ?? [];
        $inv     = $p['inverter']   ?? [];
        $chk     = $p['checks']     ?? [];
        $nrg     = $p['energy']     ?? [];
        $prot    = $p['protection'] ?? [];
        $monthly = $p['monthly']    ?? [];

        // ── Title ─────────────────────────────────────────────
        $this->addTitle($s, 'CALCULADORA FV — RESUMEN DEL SISTEMA FOTOVOLTAICO');
        $s->setCellValue("A{$this->row}", 'Diseño preliminar — Generado el ' . date('d/m/Y H:i'));
        $s->getStyle("A{$this->row}")->getFont()->setItalic(true)->setSize(9)
          ->getColor()->setARGB(self::C_LABEL_FG);
        $s->mergeCells("A{$this->row}:C{$this->row}");
        $this->row++;
        $this->row++;

        // ── Ubicación y Diseño ────────────────────────────────
        $this->addSectionHeader($s, 'UBICACIÓN Y DISEÑO');
        $this->addDataRow($s, 'Latitud',             $site['lat']     ?? '—', '°');
        $this->addDataRow($s, 'Longitud',            $site['lng']     ?? '—', '°');
        $this->addDataRow($s, 'Consumo anual',       (float)($site['consumo'] ?? 0), 'kWh/año');
        $this->addDataRow($s, 'Horas Solar Pico (HSP)', (float)($site['hsp'] ?? 0), 'h/día', 2);
        $this->addDataRow($s, 'Temperatura mínima', (float)($site['tmin'] ?? 0), '°C', 1);
        $this->addDataRow($s, 'Temperatura máxima', (float)($site['tmax'] ?? 0), '°C', 1);
        $this->row++;

        // ── Módulo FV ─────────────────────────────────────────
        $this->addSectionHeader($s, 'MÓDULO FV');
        $this->addDataRow($s, 'Fabricante',                   $mod['manufacturer']    ?? '—');
        $this->addDataRow($s, 'Modelo',                       $mod['model']           ?? '—');
        $this->addDataRow($s, 'Potencia (Pmax STC)',          $mod['pmax_stc']       ?? '—', 'Wp');
        $this->addDataRow($s, 'Tensión en vacío (Voc STC)',   $mod['voc_stc']         ?? '—', 'V');
        $this->addDataRow($s, 'Tensión en Pmpp (Vmpp STC)',   $mod['vmpp_stc']        ?? '—', 'V');
        $this->addDataRow($s, 'Corriente de cortocircuito (Isc STC)', (float)($mod['isc_stc'] ?? 0), 'A', 2);
        $this->addDataRow($s, 'Corriente en Pmpp (Imp STC)',  (float)($mod['imp_stc'] ?? 0), 'A', 2);
        $this->addDataRow($s, 'Coef. temperatura Voc (β)',    (float)($mod['temp_coeff_voc'] ?? 0), '%/°C', 2);
        $this->addDataRow($s, 'Coef. temperatura Pmax (γ)',   (float)($mod['temp_coeff_pmax'] ?? 0), '%/°C', 2);
        $this->row++;

        // ── Inversor ──────────────────────────────────────────
        $this->addSectionHeader($s, 'INVERSOR');
        $this->addDataRow($s, 'Fabricante',                  $inv['manufacturer']             ?? '—');
        $this->addDataRow($s, 'Modelo',                      $inv['model']                    ?? '—');
        $this->addDataRow($s, 'Potencia AC nominal',        (float)($inv['nominal_ac_power'] ?? 0), 'W');
        $this->addDataRow($s, 'Tipo de fase',                $inv['phase_type']               ?? '—');
        $this->addDataRow($s, 'Tensión AC nominal',          $inv['ac_voltage_nominal']       ?? '—', 'V');
        $this->addDataRow($s, 'Rango de tensión MPPT',       ($inv['mppt_voltage_min'] ?? '—') . ' – ' . ($inv['mppt_voltage_max'] ?? '—'), 'V');
        $this->addDataRow($s, 'Tensión DC máxima',           $inv['max_dc_voltage']           ?? '—', 'V');
        $this->addDataRow($s, 'Corriente máx. por MPPT',     $inv['max_input_current_per_mppt'] ?? '—', 'A');
        $this->addDataRow($s, 'Corriente de CC máx. entrada',$inv['max_short_circuit_current']  ?? '—', 'A');
        $this->addDataRow($s, 'Número de entradas MPPT',     $inv['mppt_count']               ?? '—');
        $this->row++;

        // ── Configuración de la Planta ────────────────────────
        $this->addSectionHeader($s, 'CONFIGURACIÓN DE LA PLANTA');
        $n_rem = (int)($arr['n_rem'] ?? 0);
        $Ns    = (int)($arr['Ns']    ?? 0);
        $Np    = (int)($arr['Np']    ?? 0);
        $N     = (int)($arr['N']     ?? 0);
        $N_inv = (int)($arr['N_inv'] ?? 1);
        if ($n_rem > 0) {
            $n_full       = $Np - 1;
            $stringsValue = sprintf('%d string%s × %d mód + 1 string × %d mód — string corto',
                $n_full, $n_full > 1 ? 's' : '', $Ns, $n_rem);
        } else {
            $stringsValue = sprintf('%d string%s × %d mód', $Np, $Np > 1 ? 's' : '', $Ns);
        }
        $this->addDataRow($s, 'Total de módulos (N)', $N ?: '—');
        $this->addDataRow($s, 'Strings',              $stringsValue);
        $this->addDataRow($s, 'Potencia total STC',              (float)($arr['P_stc_kW'] ?? 0), 'kWp', 2);
        $this->addDataRow($s, 'Voc del arreglo en frío (Tmin)',  (float)($arr['Voc_cold']  ?? 0), 'V', 1);
        $this->addDataRow($s, 'Vmpp del arreglo en calor (Tmax)',(float)($arr['Vmpp_hot']  ?? 0), 'V', 1);
        $this->addDataRow($s, 'Vmpp del arreglo en frío (Tmin)', (float)($arr['Vmpp_cold'] ?? 0), 'V', 1);
        $this->addDataRow($s, 'Área del arreglo neta',           (float)($arr['arrArea'] ?? 0), 'm²', 2);
        $this->addDataRow($s, 'Número de inversores',            $N_inv);

        // ── MPPT occupancy ────────────────────────────────────
        $capPerInv    = (int)($arr['cap_per_inv'] ?? 0);
        $floorStrings = ($N_inv > 0) ? (int)floor($Np / $N_inv) : 0;
        $ceilStrings  = ($N_inv > 0) ? (int)ceil($Np  / $N_inv) : 0;
        $nWithCeil    = ($N_inv > 0) ? ($Np % $N_inv) : 0;
        $nWithFloor   = $N_inv - $nWithCeil;

        if ($floorStrings === $ceilStrings) {
            $distValue = $ceilStrings . ' strings/inv';
        } else {
            $distValue = sprintf('%d inv. × %d + %d inv. × %d strings', $nWithCeil, $ceilStrings, $nWithFloor, $floorStrings);
        }
        $this->addDataRow($s, 'Distribución de strings', $distValue);

        if ($capPerInv > 0) {
            if ($floorStrings < $capPerInv) {
                if ($nWithCeil === 0) {
                    $unused = $capPerInv - $floorStrings;
                    $occupancyNote = sprintf('Cada inversor usa %d/%d entradas (%d sin usar/inv)', $floorStrings, $capPerInv, $unused);
                } elseif ($ceilStrings === $capPerInv) {
                    $unused = $capPerInv - $floorStrings;
                    $occupancyNote = sprintf('%d inversor%s con %d/%d strings (%d entrada%s sin usar)',
                        $nWithFloor, $nWithFloor > 1 ? 'es' : '', $floorStrings, $capPerInv,
                        $unused, $unused > 1 ? 's' : '');
                } else {
                    $occupancyNote = sprintf('%d inv. × %d/%d + %d inv. × %d/%d strings (máx: %d/inv)',
                        $nWithCeil, $ceilStrings, $capPerInv, $nWithFloor, $floorStrings, $capPerInv, $capPerInv);
                }
                $this->addDataRow($s, '⚠ Ocupación de entradas MPPT', $occupancyNote);
            } else {
                $this->addDataRow($s, '✓ Ocupación de entradas MPPT', 'Todos los inversores operan a plena capacidad');
            }
        }
        $this->row++;

        // ── Verificaciones de Compatibilidad ──────────────────
        $this->addSectionHeader($s, 'VERIFICACIONES DE COMPATIBILIDAD (NOM-001-SEDE-2012)');
        $this->addCompatHeader($s);
        foreach ($chk as $c) {
            $this->addCompatRow($s, $c);
        }
        $this->row++;

        // ── Estimación Energética ─────────────────────────────
        $energiaStartRow = $this->row;
        $this->addSectionHeader($s, 'ESTIMACIÓN ENERGÉTICA');
        $this->addDataRow($s, 'Producción anual estimada', (float)($nrg['E_year'] ?? 0), 'kWh/año');
        $this->addDataRow($s, 'Autosuficiencia estimada',  (float)($nrg['coverage'] ?? 0), '%', 1);
        $lossFactor = (float)($nrg['loss_factor'] ?? 0.80);
        $this->addDataRow($s, 'Factor de pérdidas (sin temp.)', round($lossFactor * 100, 2), '%');
        $this->addDataRow($s, 'NOCT del módulo', ($nrg['noct'] ?? 45) . ' °C');
        $losses = $nrg['losses'] ?? [];
        if (!empty($losses)) {
            $lossLabels = [
                'soiling'   => 'Suciedad',
                'mismatch'  => 'Desajuste',
                'dc_wiring' => 'Cableado DC',
                'inverter'  => 'Conversión inversor',
                'ac_wiring' => 'Cableado AC',
                'lid'       => 'Degradación inicial (LID)',
            ];
            foreach ($losses as $key => $pct) {
                $label = $lossLabels[$key] ?? $key;
                $this->addDataRow($s, '  — ' . $label, (float)$pct, '%');
            }
        }
        $this->addDataRow($s, 'Relación DC/CA',            (float)($nrg['dc_ac'] ?? 0), '', 2);
        $this->row++;

        // ── Inline monthly table (to the right of Estimación Energética) ──
        if (!empty($monthly) && count($monthly) === 12) {
            $this->addMonthlyTableInline($s, $monthly, $energiaStartRow, $lossFactor);
        }

        // ── Protecciones Eléctricas ───────────────────────────
        $this->addSectionHeader($s, 'PROTECCIONES ELÉCTRICAS — NOM-001-SEDE-2012, Art. 690.8');

        $deratingOn    = $prot['derating_on']     ?? false;
        $deratingFactor= $prot['derating_factor'] ?? 1.0;
        $dcScenarios   = $prot['dc_scenarios']    ?? [];
        $ac            = $prot['ac']              ?? [];

        $this->addSubHeader($s, 'Circuito DC — String → Inversor');
        if ($deratingOn) {
            $this->addDataRow(
                $s,
                sprintf('Corrección por temperatura (÷ %.2f)', (float)$deratingFactor),
                sprintf('Tabla 310.15(B)(2)(a), conductores Cu 75 °C — Tamb máx = %s °C', $prot['tmax'] ?? '—')
            );
        }

        if (empty($dcScenarios)) {
            $this->addDataRow($s, 'Escenarios DC', 'Sin datos');
        } else {
            foreach ($dcScenarios as $sc) {
                $strPerMppt  = (int)($sc['strPerMppt']  ?? 1);
                $mpptCount   = (int)($sc['mpptCount']   ?? 1);
                $needsFuse   = (bool)($sc['needsFuse']  ?? false);
                $fuseStdA    = $sc['fuseStdA']           ?? null;
                $strCircuit  = $sc['strCircuit']         ?? [];
                $mpptCircuit = $sc['mpptCircuit']        ?? [];

                $scHeader = sprintf(
                    '%d string%s/MPPT — %d entrada%s MPPT',
                    $strPerMppt, $strPerMppt > 1 ? 's' : '',
                    $mpptCount,  $mpptCount  > 1 ? 's' : ''
                );
                $this->addSubHeader($s, $scHeader);

                if ($needsFuse) {
                    $this->addDataRow($s, 'Fusible de cadena gPV', $fuseStdA !== null ? $fuseStdA . ' A' : '—');
                    $this->addDataRow($s, 'Cable cadena Cu 75°C',  $strCircuit['AWG']  ?? '—');
                    $this->addDataRow($s, 'Cable entrada MPPT',    $mpptCircuit['AWG'] ?? '—');
                    $this->addDataRow($s, 'Protección MPPT (OCPD DC)', $mpptCircuit['OCPD'] ?? '—');
                    if (!empty($strCircuit['upsized']) || !empty($mpptCircuit['upsized'])) {
                        $this->addDataRow($s, '⚠ Art. 240-4(d) NOM-001-SEDE-2012', 'Conductor aumentado por regla de conductor pequeño.');
                    }
                } else {
                    $this->addDataRow($s, 'Configuración', 'String único — sin corriente inversa posible');
                    $this->addDataRow($s, 'Cable DC Cu 75°C',    $strCircuit['AWG']  ?? '—');
                    $this->addDataRow($s, 'Protección CC (OCPD DC)', $strCircuit['OCPD'] ?? '—');
                    if (!empty($strCircuit['upsized'])) {
                        $this->addDataRow($s, '⚠ Art. 240-4(d) NOM-001-SEDE-2012', 'Conductor aumentado por regla de conductor pequeño.');
                    }
                }
            }
        }
        $this->row++;

        $this->addSubHeader($s, 'Circuito AC — Inversor → Tablero');
        $this->addDataRow($s, 'Tipo de fase',                         $ac['phase_type'] ?? '—');
        $this->addDataRow($s, 'Corriente base AC (P ÷ V)',            (float)($ac['I_base']   ?? 0), 'A', 2);
        $this->addDataRow($s, 'Corriente de diseño AC (× 1.25)',      (float)($ac['I_design']  ?? 0), 'A', 2);
        if ($deratingOn) {
            $this->addDataRow(
                $s,
                sprintf('Corriente requerida en tabla AC (÷ %.2f)', (float)$deratingFactor),
                (float)($ac['I_required'] ?? 0),
                'A',
                2
            );
        }
        $this->addDataRow($s, 'Protección recomendada (OCPD AC)',      $ac['OCPD'] ?? '—');
        $this->addDataRow($s, 'Calibre conductor AC',                  $ac['AWG']  ?? '—');
        if (!empty($ac['small_conductor_upsized'])) {
            $this->addDataRow(
                $s,
                '⚠ Nota Art. 240-4(d) NOM-001-SEDE-2012',
                'Calibre aumentado por regla de conductor pequeño.'
            );
        }
        $this->row++;

        // Footer note
        $s->setCellValue("A{$this->row}", 'Nota: Este cálculo es un diseño preliminar. Los resultados deben ser verificados por un ingeniero certificado antes de la instalación.');
        $s->getStyle("A{$this->row}")->getFont()->setItalic(true)->setSize(8)
          ->getColor()->setARGB(self::C_LABEL_FG);
        $s->mergeCells("A{$this->row}:C{$this->row}");
    }

    // ── Row helpers ───────────────────────────────────────────
    private function addTitle(Worksheet $s, string $text): void
    {
        $s->setCellValue("A{$this->row}", $text);
        $s->mergeCells("A{$this->row}:C{$this->row}");
        $s->getStyle("A{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::C_TITLE_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_TITLE_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension($this->row)->setRowHeight(26);
        $this->row++;
    }

    private function addSectionHeader(Worksheet $s, string $text): void
    {
        $s->setCellValue("A{$this->row}", $text);
        $s->mergeCells("A{$this->row}:C{$this->row}");
        $s->getStyle("A{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SECTION_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension($this->row)->setRowHeight(18);
        $this->row++;
    }

    private function addSubHeader(Worksheet $s, string $text): void
    {
        $s->setCellValue("A{$this->row}", $text);
        $s->mergeCells("A{$this->row}:C{$this->row}");
        $s->getStyle("A{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => self::C_SUBHDR_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SUBHDR_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 2],
        ]);
        $this->row++;
    }

    private function addCompatHeader(Worksheet $s): void
    {
        $s->setCellValue("A{$this->row}", 'Verificación');
        $s->setCellValue("B{$this->row}", 'Detalle');
        $s->setCellValue("C{$this->row}", 'Resultado');
        $s->getStyle("A{$this->row}:C{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => self::C_SUBHDR_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SUBHDR_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $this->row++;
    }

    /**
     * @param array{label:string, detail:string, pass:bool, hard:bool} $c
     */
    private function addCompatRow(Worksheet $s, array $c): void
    {
        $pass = (bool)($c['pass'] ?? true);
        $hard = (bool)($c['hard'] ?? false);

        if ($pass) {
            $resultText = 'PASA';
            $bg         = self::C_PASS_BG;
            $fg         = self::C_PASS_FG;
        } elseif ($hard) {
            $resultText = 'FALLA';
            $bg         = self::C_FAIL_BG;
            $fg         = self::C_FAIL_FG;
        } else {
            $resultText = 'ADVERTENCIA';
            $bg         = self::C_WARN_BG;
            $fg         = self::C_WARN_FG;
        }

        $rowBg = ($this->row % 2 === 0) ? 'FFFFFFFF' : self::C_ODD_BG;

        $s->setCellValue("A{$this->row}", $c['label']  ?? '');
        $s->setCellValue("B{$this->row}", $c['detail'] ?? '');
        $s->setCellValue("C{$this->row}", $resultText);

        // Row background for A and B
        $s->getStyle("A{$this->row}:B{$this->row}")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
            'font'      => ['size' => 9],
            'alignment' => ['indent' => 1],
        ]);

        // Colored result cell C
        $s->getStyle("C{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $fg]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $this->row++;
    }

    private function addDataRow(Worksheet $s, string $label, mixed $value, string $unit = '', ?int $decimals = null): void
    {
        $bg = ($this->row % 2 === 0) ? 'FFFFFFFF' : self::C_ODD_BG;

        $s->setCellValue("A{$this->row}", $label);

        // If the incoming value is numeric, write it as a number and apply
        // a number format so Excel recognizes it. Use the provided $decimals
        // when present; otherwise pick a sensible default.
        if (is_numeric($value)) {
            $numeric = (float) $value;
            $s->setCellValue("B{$this->row}", $numeric);

            if ($decimals !== null) {
                $format = '#,##0';
                if ($decimals > 0) {
                    $format .= '.' . str_repeat('0', $decimals);
                }
            } else {
                $format = (floor($numeric) != $numeric) ? '#,##0.00' : '#,##0';
            }

            $s->getStyle("B{$this->row}")->getNumberFormat()->setFormatCode($format);
        } else {
            $s->setCellValue("B{$this->row}", $value);
        }

        if ($unit !== '') {
            $s->setCellValue("C{$this->row}", $unit);
        }

        $s->getStyle("A{$this->row}")->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['argb' => self::C_LABEL_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            'alignment' => ['indent' => 2],
        ]);
        $s->getStyle("B{$this->row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        if ($unit !== '') {
            $s->getStyle("C{$this->row}")->applyFromArray([
                'font'      => ['size' => 9, 'color' => ['argb' => self::C_LABEL_FG]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            ]);
        }

        $this->row++;
    }
    // ── Inline monthly table (Sheet 1, right of Estimación Energética) ────
    private function addMonthlyTableInline(Worksheet $s, array $monthly, int $startRow, float $lossFactor): void
    {
        $hasConsumption = array_reduce($monthly, fn($carry, $m) => $carry || isset($m['consumo']), false);
        $colCount   = $hasConsumption ? 11 : 8;
        $base       = 4; // E = chr(65+4) = 'E'
        $col        = fn(int $offset) => chr(65 + $base + $offset);
        $firstCol   = $col(0);  // 'E'
        $lastCol    = $col($colCount - 1);

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $monthDays  = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $r = $startRow;

        // ── Title ────────────────────────────────────────────────
        $s->setCellValue("{$firstCol}{$r}", 'PRODUCCIÓN MENSUAL ESTIMADA');
        $s->mergeCells("{$firstCol}{$r}:{$lastCol}{$r}");
        $s->getStyle("{$firstCol}{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => self::C_TITLE_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_TITLE_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $r++;

        // ── Column headers ────────────────────────────────────────
        $headers = [
            'Mes',
            "GHI\n(kWh/m²/d)",
            "POA\n(kWh/m²/d)",
            "Tamb\n(°C)",
            "Tcel\n(°C)",
            "ftemp\n(%)",
            'Días',
            "Producción\n(kWh)",
        ];
        if ($hasConsumption) {
            $headers[] = "Consumo\n(kWh)";
            $headers[] = "Balance\n(kWh)";
            $headers[] = "Bolsa\n(kWh)";
        }
        foreach ($headers as $i => $h) {
            $s->setCellValue("{$col($i)}{$r}", $h);
        }
        $s->getStyle("{$firstCol}{$r}:{$lastCol}{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SECTION_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);
        $s->getRowDimension($r)->setRowHeight(28);
        $r++;

        // ── Data rows ─────────────────────────────────────────────
        $totalProd = 0.0;
        $totalCons = 0.0;
        $bolsa     = 0.0;

        foreach ($monthly as $i => $m) {
            $bg   = ($i % 2 === 0) ? 'FFFFFFFF' : self::C_ODD_BG;
            $prod = (float)($m['production'] ?? 0);
            $totalProd += $prod;

            $s->setCellValue("{$col(0)}{$r}", $monthNames[$i] ?? '—');
            $s->setCellValue("{$col(1)}{$r}", (float)($m['ghi'] ?? 0));
            $s->getStyle("{$col(1)}{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $s->setCellValue("{$col(2)}{$r}", round((float)($m['poa'] ?? $m['ghi'] ?? 0), 2));
            $s->getStyle("{$col(2)}{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $s->setCellValue("{$col(3)}{$r}", round((float)($m['t2m_avg'] ?? 0), 1));
            $s->setCellValue("{$col(4)}{$r}", round((float)($m['T_cell'] ?? 0), 1));
            $fTempPct = (((float)($m['f_temp'] ?? 1)) - 1) * 100;
            $s->setCellValue("{$col(5)}{$r}", round($fTempPct, 1));
            $s->setCellValue("{$col(6)}{$r}", $monthDays[$i]);
            $s->setCellValue("{$col(7)}{$r}", (int)round($prod));

            $s->getStyle("{$firstCol}{$r}:{$lastCol}{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'font'      => ['size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $s->getStyle("{$col(0)}{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            if ($hasConsumption) {
                if (isset($m['consumo'])) {
                    $cons    = (float)$m['consumo'];
                    $balance = (float)($m['balance'] ?? ($prod - $cons));
                    $totalCons += $cons;
                    $bolsa     += $balance;

                    $s->setCellValue("{$col(8)}{$r}", (int)round($cons));
                    $s->setCellValue("{$col(9)}{$r}", ($balance >= 0 ? '+' : '') . (int)round($balance));
                    $balFg = $balance >= 0 ? self::C_PASS_FG : self::C_FAIL_FG;
                    $balBg = $balance >= 0 ? self::C_PASS_BG : self::C_FAIL_BG;
                    $s->getStyle("{$col(9)}{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => $balFg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $balBg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    $bolsaFg = $bolsa >= 0 ? self::C_PASS_FG : self::C_FAIL_FG;
                    $bolsaBg = $bolsa >= 0 ? self::C_PASS_BG : self::C_FAIL_BG;
                    $s->setCellValue("{$col(10)}{$r}", ($bolsa >= 0 ? '+' : '') . (int)round($bolsa));
                    $s->getStyle("{$col(10)}{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => $bolsaFg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bolsaBg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                } else {
                    $s->setCellValue("{$col(8)}{$r}", '—');
                    $s->setCellValue("{$col(9)}{$r}", '—');
                    $s->setCellValue("{$col(10)}{$r}", '—');
                }
            }
            $r++;
        }

        // ── Total row ─────────────────────────────────────────────
        $s->setCellValue("{$col(0)}{$r}", 'Total anual');
        foreach ([1, 2, 3, 4, 5] as $ci) {
            $s->setCellValue("{$col($ci)}{$r}", '—');
        }
        $s->setCellValue("{$col(6)}{$r}", 365);
        $s->setCellValue("{$col(7)}{$r}", (int)round($totalProd));
        $s->getStyle("{$firstCol}{$r}:{$lastCol}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SECTION_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $s->getStyle("{$col(0)}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        if ($hasConsumption) {
            $s->setCellValue("{$col(8)}{$r}", $totalCons > 0 ? (int)round($totalCons) : '—');
            $s->setCellValue("{$col(9)}{$r}", '—');
            if ($totalCons > 0) {
                $bolsaFg = $bolsa >= 0 ? self::C_PASS_FG : self::C_FAIL_FG;
                $s->setCellValue("{$col(10)}{$r}", ($bolsa >= 0 ? '+' : '') . (int)round($bolsa));
                $s->getStyle("{$col(10)}{$r}")->getFont()->getColor()->setARGB($bolsaFg);
            } else {
                $s->setCellValue("{$col(10)}{$r}", '—');
            }
        }
        $r++;

        // ── Note ──────────────────────────────────────────────────
        $lfPct = round($lossFactor * 100, 1);
        $note  = "Producción estimada: P_STC × POA × días × f_temp(Faiman) × factor pérdidas ({$lfPct}%).";
        $s->setCellValue("{$firstCol}{$r}", $note);
        $s->mergeCells("{$firstCol}{$r}:{$lastCol}{$r}");
        $s->getStyle("{$firstCol}{$r}")->getFont()->setItalic(true)->setSize(8)
          ->getColor()->setARGB(self::C_LABEL_FG);
    }
    // ── Utility ───────────────────────────────────────────────
    /** @param array<string, int|float> $widths */
    private function setColumnWidths(Worksheet $s, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }
    }
}
