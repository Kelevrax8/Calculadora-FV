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
        $this->setColumnWidths($sheet, ['A' => 42, 'B' => 28, 'C' => 14]);

        $this->buildResumen($sheet, $payload);

        // Sheet 2: monthly production (only if data present)
        if (!empty($payload['monthly']) && count($payload['monthly']) === 12) {
            $monthly = $spreadsheet->createSheet();
            $monthly->setTitle('Producción Mensual');
            $this->buildMonthly($monthly, $payload['monthly']);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
    }

    // ── Sheet 1 builder ───────────────────────────────────────
    private function buildResumen(Worksheet $s, array $p): void
    {
        $site  = $p['site']       ?? [];
        $mod   = $p['module']     ?? [];
        $arr   = $p['array']      ?? [];
        $inv   = $p['inverter']   ?? [];
        $chk   = $p['checks']     ?? [];
        $nrg   = $p['energy']     ?? [];
        $prot  = $p['protection'] ?? [];

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
        $this->addDataRow($s, 'Consumo anual',       number_format((float)($site['consumo'] ?? 0), 0, '.', ','), 'kWh/año');
        $this->addDataRow($s, 'Horas Solar Pico (HSP)', number_format((float)($site['hsp'] ?? 0), 2), 'h/día');
        $this->addDataRow($s, 'Temperatura mínima', number_format((float)($site['tmin'] ?? 0), 1), '°C');
        $this->addDataRow($s, 'Temperatura máxima', number_format((float)($site['tmax'] ?? 0), 1), '°C');
        $this->row++;

        // ── Módulo FV ─────────────────────────────────────────
        $this->addSectionHeader($s, 'MÓDULO FV');
        $this->addDataRow($s, 'Fabricante',                   $mod['manufacturer']    ?? '—');
        $this->addDataRow($s, 'Modelo',                       $mod['model']           ?? '—');
        $this->addDataRow($s, 'Potencia (Pmax STC)',          $mod['pmax_stc']        ?? '—', 'Wp');
        $this->addDataRow($s, 'Tensión en vacío (Voc STC)',   $mod['voc_stc']         ?? '—', 'V');
        $this->addDataRow($s, 'Tensión en Pmpp (Vmpp STC)',   $mod['vmpp_stc']        ?? '—', 'V');
        $this->addDataRow($s, 'Corriente de cortocircuito (Isc STC)', $mod['isc_stc'] ?? '—', 'A');
        $this->addDataRow($s, 'Corriente en Pmpp (Imp STC)',  $mod['imp_stc']         ?? '—', 'A');
        $this->addDataRow($s, 'Coef. temperatura Voc (β)',    $mod['temp_coeff_voc']  ?? '—', '%/°C');
        $this->addDataRow($s, 'Coef. temperatura Pmax (γ)',   $mod['temp_coeff_pmax'] ?? '—', '%/°C');
        $this->row++;

        // ── Configuración del Arreglo ─────────────────────────
        $this->addSectionHeader($s, 'CONFIGURACIÓN DEL ARREGLO');
        $this->addDataRow($s, 'Módulos por string',               $arr['Ns']         ?? '—');
        $this->addDataRow($s, 'Número de strings',               $arr['Np']         ?? '—');
        $this->addDataRow($s, 'Total de módulos (N)',            $arr['N']          ?? '—');
        $this->addDataRow($s, 'Potencia total STC',              number_format((float)($arr['P_stc_kW'] ?? 0), 2), 'kWp');
        $this->addDataRow($s, 'Voc del arreglo en frío (Tmin)',  number_format((float)($arr['Voc_cold']  ?? 0), 1), 'V');
        $this->addDataRow($s, 'Vmpp del arreglo en calor (Tmax)',number_format((float)($arr['Vmpp_hot']  ?? 0), 1), 'V');
        $this->addDataRow($s, 'Vmpp del arreglo en frío (Tmin)', number_format((float)($arr['Vmpp_cold'] ?? 0), 1), 'V');
        $this->addDataRow($s, 'Área del arreglo',                 number_format((float)($arr['arrArea'] ?? 0), 2), 'm²');
        $this->row++;

        // ── Inversor ──────────────────────────────────────────
        $this->addSectionHeader($s, 'INVERSOR');
        $this->addDataRow($s, 'Fabricante',                  $inv['manufacturer']             ?? '—');
        $this->addDataRow($s, 'Modelo',                      $inv['model']                    ?? '—');
        $this->addDataRow($s, 'Potencia AC nominal',         number_format((float)($inv['nominal_ac_power'] ?? 0), 0, '.', ','), 'W');
        $this->addDataRow($s, 'Tipo de fase',                $inv['phase_type']               ?? '—');
        $this->addDataRow($s, 'Tensión AC nominal',          $inv['ac_voltage_nominal']       ?? '—', 'V');
        $this->addDataRow($s, 'Rango de tensión MPPT',       ($inv['mppt_voltage_min'] ?? '—') . ' – ' . ($inv['mppt_voltage_max'] ?? '—'), 'V');
        $this->addDataRow($s, 'Tensión DC máxima',           $inv['max_dc_voltage']           ?? '—', 'V');
        $this->addDataRow($s, 'Corriente máx. por MPPT',     $inv['max_input_current_per_mppt'] ?? '—', 'A');
        $this->addDataRow($s, 'Corriente de CC máx. entrada',$inv['max_short_circuit_current']  ?? '—', 'A');
        $this->addDataRow($s, 'Número de entradas MPPT',     $inv['mppt_count']               ?? '—');
        $this->row++;

        // ── Verificaciones de Compatibilidad ──────────────────
        $this->addSectionHeader($s, 'VERIFICACIONES DE COMPATIBILIDAD (NOM-001-SEDE-2012)');
        $this->addCompatHeader($s);
        foreach ($chk as $c) {
            $this->addCompatRow($s, $c);
        }
        $this->row++;

        // ── Estimación Energética ─────────────────────────────
        $this->addSectionHeader($s, 'ESTIMACIÓN ENERGÉTICA');
        $this->addDataRow($s, 'Producción anual estimada', number_format((float)($nrg['E_year'] ?? 0), 0, '.', ','), 'kWh/año');
        $this->addDataRow($s, 'Autosuficiencia estimada',  number_format((float)($nrg['coverage'] ?? 0), 1), '%');
        $this->addDataRow($s, 'Factor de rendimiento (PR)',(int)(($nrg['PR'] ?? 0) * 100), '%');
        $this->addDataRow($s, 'Relación DC/CA',            number_format((float)($nrg['dc_ac'] ?? 0), 2));
        $this->row++;

        // ── Protecciones Eléctricas ───────────────────────────
        $this->addSectionHeader($s, 'PROTECCIONES ELÉCTRICAS — NOM-001-SEDE-2012, Art. 690.8');

        $deratingOn    = $prot['derating_on']     ?? false;
        $deratingFactor= $prot['derating_factor'] ?? 1.0;
        $dc            = $prot['dc']              ?? [];
        $ac            = $prot['ac']              ?? [];

        $this->addSubHeader($s, 'Circuito DC — String → Inversor');
        $this->addDataRow($s, 'Isc del módulo',                       number_format((float)($dc['isc_module'] ?? 0), 2), 'A');
        $this->addDataRow($s, 'Corriente de diseño DC (Isc × 1.56)',  number_format((float)($dc['I_design']  ?? 0), 2), 'A');
        if ($deratingOn) {
            $this->addDataRow(
                $s,
                sprintf('Corriente requerida en tabla DC (÷ %.2f)', (float)$deratingFactor),
                number_format((float)($dc['I_required'] ?? 0), 2),
                'A'
            );
        }
        $this->addDataRow($s, 'Protección recomendada (OCPD DC)',      $dc['OCPD'] ?? '—');
        $this->addDataRow($s, 'Calibre conductor DC',                  $dc['AWG']  ?? '—');
        $this->row++;

        $this->addSubHeader($s, 'Circuito AC — Inversor → Tablero');
        $this->addDataRow($s, 'Tipo de fase',                         $ac['phase_type'] ?? '—');
        $this->addDataRow($s, 'Corriente base AC (P ÷ V)',            number_format((float)($ac['I_base']   ?? 0), 2), 'A');
        $this->addDataRow($s, 'Corriente de diseño AC (× 1.25)',      number_format((float)($ac['I_design']  ?? 0), 2), 'A');
        if ($deratingOn) {
            $this->addDataRow(
                $s,
                sprintf('Corriente requerida en tabla AC (÷ %.2f)', (float)$deratingFactor),
                number_format((float)($ac['I_required'] ?? 0), 2),
                'A'
            );
        }
        $this->addDataRow($s, 'Protección recomendada (OCPD AC)',      $ac['OCPD'] ?? '—');
        $this->addDataRow($s, 'Calibre conductor AC',                  $ac['AWG']  ?? '—');
        $this->row++;

        $deratingText = $deratingOn
            ? sprintf('Aplicada — Tamb máx. = %.1f °C → factor %.2f (Tabla 310.15(B)(2)(a), conductores 75 °C)',
                (float)($prot['tmax'] ?? 0), (float)$deratingFactor)
            : 'No aplicada (conductores a temperatura estándar)';
        $this->addDataRow($s, 'Corrección por temperatura', $deratingText);
        $this->row++;

        // Footer note
        $s->setCellValue("A{$this->row}", 'Nota: Este cálculo es un diseño preliminar. Los resultados deben ser verificados por un ingeniero certificado antes de la instalación.');
        $s->getStyle("A{$this->row}")->getFont()->setItalic(true)->setSize(8)
          ->getColor()->setARGB(self::C_LABEL_FG);
        $s->mergeCells("A{$this->row}:C{$this->row}");
    }

    // ── Sheet 2 builder ───────────────────────────────────────
    private function buildMonthly(Worksheet $s, array $monthly): void
    {
        // Detect whether consumption data was entered by the user
        $hasConsumption = array_reduce($monthly, fn($carry, $m) => $carry || isset($m['consumo']), false);

        $colCount = $hasConsumption ? 6 : 4;
        $lastCol  = chr(64 + $colCount); // D or F

        $widths = ['A' => 18, 'B' => 20, 'C' => 10, 'D' => 24];
        if ($hasConsumption) {
            $widths['E'] = 22;
            $widths['F'] = 22;
        }
        $this->setColumnWidths($s, $widths);

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $monthDays  = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Title
        $s->setCellValue('A1', 'PRODUCCIÓN MENSUAL ESTIMADA');
        $s->mergeCells("A1:{$lastCol}1");
        $s->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => self::C_TITLE_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_TITLE_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $s->getRowDimension(1)->setRowHeight(22);

        // Column headers
        $headers = ['Mes', 'GHI diario (kWh/m²)', 'Días', 'Producción estimada (kWh)'];
        if ($hasConsumption) {
            $headers[] = 'Consumo real (kWh)';
            $headers[] = 'Balance (kWh)';
        }
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $s->setCellValue("{$col}2", $h);
        }
        $s->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SECTION_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $totalProd = 0.0;
        $totalCons = 0.0;
        $totalBal  = 0.0;

        foreach ($monthly as $i => $m) {
            $r    = $i + 3;
            $bg   = ($i % 2 === 0) ? 'FFFFFFFF' : self::C_ODD_BG;
            $prod = (float)($m['production'] ?? 0);
            $totalProd += $prod;

            $s->setCellValue("A{$r}", $monthNames[$i] ?? '—');
            $s->setCellValue("B{$r}", number_format((float)($m['ghi'] ?? 0), 2));
            $s->setCellValue("C{$r}", $monthDays[$i]);
            $s->setCellValue("D{$r}", (int)round($prod));

            $s->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $s->getStyle("A{$r}")->getFont()->setBold(true);

            if ($hasConsumption) {
                if (isset($m['consumo'])) {
                    $cons    = (float)$m['consumo'];
                    $balance = (float)($m['balance'] ?? ($prod - $cons));
                    $totalCons += $cons;
                    $totalBal  += $balance;

                    $s->setCellValue("E{$r}", (int)round($cons));
                    $s->setCellValue("F{$r}", ($balance >= 0 ? '+' : '') . (int)round($balance));

                    // Color balance cell: green if surplus, red if deficit
                    $balFg = $balance >= 0 ? self::C_PASS_FG : self::C_FAIL_FG;
                    $balBg = $balance >= 0 ? self::C_PASS_BG : self::C_FAIL_BG;
                    $s->getStyle("F{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => $balFg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $balBg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                } else {
                    $s->setCellValue("E{$r}", '—');
                    $s->setCellValue("F{$r}", '—');
                }
            }
        }

        // Total row
        $r = count($monthly) + 3;
        $s->setCellValue("A{$r}", 'Total anual');
        $s->setCellValue("B{$r}", '—');
        $s->setCellValue("C{$r}", 365);
        $s->setCellValue("D{$r}", (int)round($totalProd));

        $s->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::C_SECTION_FG]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::C_SECTION_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        if ($hasConsumption) {
            $s->setCellValue("E{$r}", $totalCons > 0 ? (int)round($totalCons) : '—');

            if ($totalCons > 0) {
                $s->setCellValue("F{$r}", ($totalBal >= 0 ? '+' : '') . (int)round($totalBal));
                $balFg = $totalBal >= 0 ? self::C_PASS_FG : self::C_FAIL_FG;
                $s->getStyle("F{$r}")->getFont()->getColor()->setARGB($balFg);
            } else {
                $s->setCellValue("F{$r}", '—');
            }
        }

        // Note
        $noteRow = $r + 2;
        $s->setCellValue("A{$noteRow}", 'Producción estimada: P_STC (kWp) × GHI diario × días del mes × PR (0.75)');
        $s->getStyle("A{$noteRow}")->getFont()->setItalic(true)->setSize(8)
          ->getColor()->setARGB(self::C_LABEL_FG);
        $s->mergeCells("A{$noteRow}:{$lastCol}{$noteRow}");
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

    private function addDataRow(Worksheet $s, string $label, mixed $value, string $unit = ''): void
    {
        $bg = ($this->row % 2 === 0) ? 'FFFFFFFF' : self::C_ODD_BG;

        $s->setCellValue("A{$this->row}", $label);
        $s->setCellValue("B{$this->row}", $value);
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
        ]);
        if ($unit !== '') {
            $s->getStyle("C{$this->row}")->applyFromArray([
                'font'      => ['size' => 9, 'color' => ['argb' => self::C_LABEL_FG]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
            ]);
        }

        $this->row++;
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
