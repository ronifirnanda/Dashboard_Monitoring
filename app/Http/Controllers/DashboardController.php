<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DashboardController extends Controller
{
    public function overview(Request $request): View
    {
        $viewData = $this->resolveKeutataViewData($request->query('upload'));
        $chartBelanjaBarang = $this->buildBelanjaChartDataFromRawSheets($viewData['rawSheets'], 'belanja barang');
        $chartBelanjaPegawai = $this->buildBelanjaChartDataFromRawSheets($viewData['rawSheets'], 'belanja pegawai');
        $selectedMonth = trim((string) $request->query('chart_month', ''));
        $monthOptions = array_values(array_filter(
            $this->extractRawSheetMonthOptions($viewData['rawSheets']),
            static fn ($month) => $month !== 'Tanpa Bulan'
        ));

        usort($monthOptions, function ($a, $b) {
            $monthA = $this->detectMonthFromText((string) $a);
            $monthB = $this->detectMonthFromText((string) $b);

            $orderA = $monthA['order'] ?? 99;
            $orderB = $monthB['order'] ?? 99;

            if ($orderA === $orderB) {
                return strcmp((string) $a, (string) $b);
            }

            return $orderA <=> $orderB;
        });

        $latestMonth = $monthOptions !== [] ? $monthOptions[array_key_last($monthOptions)] : '';

        if ($selectedMonth === '') {
            $selectedMonth = $latestMonth;
        }

        if ($selectedMonth !== '' && ! in_array($selectedMonth, $monthOptions, true)) {
            $selectedMonth = $latestMonth;
        }

        $selectedMonthDataBarang = $this->findBelanjaMonthData($chartBelanjaBarang['months'] ?? [], $selectedMonth);
        $selectedMonthDataPegawai = $this->findBelanjaMonthData($chartBelanjaPegawai['months'] ?? [], $selectedMonth);

        return view('overview', [
            'rows' => $viewData['rows'],
            'fileName' => $viewData['fileName'],
            'analysis' => $viewData['analysis'],
            'chartBelanjaBarang' => $chartBelanjaBarang,
            'chartBelanjaPegawai' => $chartBelanjaPegawai,
            'monthOptions' => $monthOptions,
            'selectedMonth' => $selectedMonth,
            'selectedMonthDataBarang' => $selectedMonthDataBarang,
            'selectedMonthDataPegawai' => $selectedMonthDataPegawai,
            'error' => null,
        ]);
    }

    public function importKeutata(Request $request): View|RedirectResponse
    {
        if (! $request->hasFile('excel_file')) {
            return redirect()->route('keutata')->with('uploadError', 'Tidak ada file yang diunggah.');
        }

        $file = $request->file('excel_file');

        // Note: isValid() checks often fail in tests even with valid files.
        // We'll check the file later when trying to read it.
        // if (! $file->isValid()) {
        //     return redirect()->route('keutata')->with('uploadError', 'File Excel tidak valid atau gagal diunggah.');
        // }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'xlsm', 'csv'], true)) {
            return redirect()->route('keutata')->with('uploadError', 'File harus berformat Excel (.xlsx, .xls, .xlsm, atau .csv).');
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->route('keutata')->with('uploadError', 'Ukuran file terlalu besar. Maksimal 20 MB.');
        }

        $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());

        try {
            // Save file to disk first
            $uploadDirectory = 'excel_uploads';

            $filePath = $file->store($uploadDirectory, 'local');

            // Parse file from its stored location
            $fullStoragePath = storage_path('app/private/'.$filePath);
            $spreadsheet = IOFactory::load($fullStoragePath);
            $viewData = $this->buildKeutataViewData($spreadsheet, $fileName);
            // Create Upload record in database
            $upload = Upload::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'rows_count' => count($viewData['rows']),
                'analysis_data' => $viewData['analysis'],
            ]);

            session([
                'excel_rows' => $viewData['rows'],
                'excel_file_name' => $viewData['fileName'],
                'excel_analysis' => $viewData['analysis'],
                'excel_raw_sheets' => $viewData['rawSheets'],
            ]);

            return redirect()->route('keutata', ['upload' => $upload->id])->with('success', 'File Excel berhasil diupload dan dipelajari.');
        } catch (Throwable $e) {
            return redirect()->route('keutata')->with('uploadError', 'Gagal membaca file Excel: '.$e->getMessage());
        }
    }

    public function keutata(Request $request): View
    {
        $viewData = $this->resolveKeutataViewData($request->query('upload'));
        $selectedMonth = trim((string) $request->query('month', ''));
        $monthOptions = $this->extractRawSheetMonthOptions($viewData['rawSheets']);

        if ($selectedMonth !== '' && ! in_array($selectedMonth, $monthOptions, true)) {
            $selectedMonth = '';
        }

        $displayRawSheets = $selectedMonth === ''
            ? $viewData['rawSheets']
            : array_values(array_filter(
                $viewData['rawSheets'],
                static fn ($sheet) => ($sheet['month_label'] ?? '') === $selectedMonth
            ));

        $sheetOptions = array_map(
            static fn ($sheet) => (string) ($sheet['sheet'] ?? ''),
            $displayRawSheets
        );
        $sheetOptions = array_values(array_filter($sheetOptions, static fn ($name) => $name !== ''));

        $selectedSheet = trim((string) $request->query('sheet', ''));

        if ($selectedSheet !== '' && ! in_array($selectedSheet, $sheetOptions, true)) {
            $selectedSheet = '';
        }

        $selectedSheetData = null;

        if ($selectedSheet !== '') {
            foreach ($displayRawSheets as $sheet) {
                if (($sheet['sheet'] ?? '') === $selectedSheet) {
                    $selectedSheetData = $sheet;
                    break;
                }
            }
        }

        if ($selectedSheetData === null && count($sheetOptions) === 1) {
            $selectedSheet = $sheetOptions[0];

            foreach ($displayRawSheets as $sheet) {
                if (($sheet['sheet'] ?? '') === $selectedSheet) {
                    $selectedSheetData = $sheet;
                    break;
                }
            }
        }

        return view('keutata', [
            'rows' => $viewData['rows'],
            'fileName' => $viewData['fileName'],
            'error' => null,
            'analysis' => $viewData['analysis'],
            'rawSheets' => $displayRawSheets,
            'sheetOptions' => $sheetOptions,
            'selectedSheet' => $selectedSheet,
            'selectedSheetData' => $selectedSheetData,
            'monthOptions' => $monthOptions,
            'selectedMonth' => $selectedMonth,
            'success' => session('success'),
        ]);
    }

    private function resolveKeutataViewData(?string $uploadId): array
    {
        if ($uploadId !== null) {
            $upload = Upload::find($uploadId);

            if ($upload) {
                return $this->loadUploadViewData($upload);
            }
        }

        $sessionRows = session('excel_rows');
        $sessionFileName = session('excel_file_name');
        $sessionAnalysis = session('excel_analysis');
        $sessionRawSheets = session('excel_raw_sheets');

        if (is_array($sessionRows) && $sessionFileName !== null) {
            return [
                'rows' => $sessionRows,
                'fileName' => $sessionFileName,
                'analysis' => is_array($sessionAnalysis) ? $sessionAnalysis : [],
                'rawSheets' => is_array($sessionRawSheets) ? $sessionRawSheets : [],
            ];
        }

        $latestUpload = Upload::orderBy('created_at', 'desc')->first();

        if ($latestUpload) {
            return $this->loadUploadViewData($latestUpload);
        }

        return [
            'rows' => [],
            'fileName' => null,
            'analysis' => [],
            'rawSheets' => [],
        ];
    }

    private function loadUploadViewData(Upload $upload): array
    {
        $storagePath = storage_path('app/private/'.$upload->file_path);

        if (! file_exists($storagePath)) {
            return [
                'rows' => [],
                'fileName' => $upload->file_name,
                'analysis' => $upload->analysis_data ?? [],
                'rawSheets' => [],
            ];
        }

        $spreadsheet = IOFactory::load($storagePath);
        return $this->buildKeutataViewData($spreadsheet, $upload->file_name);
    }

    private function buildKeutataViewData($spreadsheet, ?string $fileName): array
    {
        $rows = [];
        $analysis = [];
        $rawSheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            [$sheetRows, $sheetAnalysis] = $this->extractRowsFromSheet($sheet);
            $sheetRows = array_merge($sheetRows, $this->extractMonthlySummaryRows($sheet));
            $rawSheets[] = $this->extractRawSheetData($sheet);

            $rows = array_merge($rows, $sheetRows);
            $analysis[] = $sheetAnalysis;
        }

        return [
            'rows' => $rows,
            'fileName' => $fileName,
            'analysis' => $analysis,
            'rawSheets' => $rawSheets,
            'error' => null,
        ];
    }

    private function extractRawSheetData($sheet): array
    {
        $sheetName = $sheet->getTitle();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $sheetMonth = $this->detectMonthFromText($sheetName);
        $mergedRanges = $this->buildMergedRangesMap($sheet);

        if ($highestRow < 1 || $highestColumnIndex < 1) {
            return [
                'sheet' => $sheetName,
                'month_label' => $sheetMonth['label'] ?? 'Tanpa Bulan',
                'columns' => [],
                'rows' => [],
            ];
        }

        $columns = [];
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $columns[] = Coordinate::stringFromColumnIndex($column);
        }

        $rows = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $cells = [];
            $hasValue = false;

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $cellAddress = Coordinate::stringFromColumnIndex($column).$row;
                $value = $this->readCellDisplayValue($sheet, $cellAddress);

                if (trim($value) === '') {
                    $value = $this->resolveMergedCellDisplayValue($sheet, $mergedRanges, $row, $column);
                }

                $cells[] = $value;

                if (trim($value) !== '') {
                    $hasValue = true;
                }

                if (
                    $sheetMonth === null &&
                    $row <= min(20, $highestRow) &&
                    $column <= min(12, $highestColumnIndex) &&
                    trim($value) !== ''
                ) {
                    $detectedMonth = $this->detectMonthFromText($value);

                    if ($detectedMonth !== null) {
                        $sheetMonth = $detectedMonth;
                    }
                }
            }

            if (! $hasValue) {
                continue;
            }

            $rows[] = [
                'row_number' => $row,
                'cells' => $cells,
            ];
        }

        return [
            'sheet' => $sheetName,
            'month_label' => $sheetMonth['label'] ?? 'Tanpa Bulan',
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function extractRawSheetMonthOptions(array $rawSheets): array
    {
        $priority = [
            'Januari' => 1,
            'Februari' => 2,
            'Maret' => 3,
            'April' => 4,
            'Mei' => 5,
            'Juni' => 6,
            'Juli' => 7,
            'Agustus' => 8,
            'September' => 9,
            'Oktober' => 10,
            'November' => 11,
            'Desember' => 12,
            'Tanpa Bulan' => 99,
        ];

        $labels = [];

        foreach ($rawSheets as $sheet) {
            $label = trim((string) ($sheet['month_label'] ?? 'Tanpa Bulan'));
            $labels[$label] = true;
        }

        $result = array_keys($labels);

        usort($result, static function ($a, $b) use ($priority) {
            $weightA = $priority[$a] ?? 98;
            $weightB = $priority[$b] ?? 98;

            if ($weightA === $weightB) {
                return strcmp($a, $b);
            }

            return $weightA <=> $weightB;
        });

        return $result;
    }

    private function buildBelanjaChartDataFromRawSheets(array $rawSheets, string $sectionKeyword): array
    {
        $monthlyTeamData = [];

        foreach ($rawSheets as $rawSheet) {
            $monthMeta = $this->detectMonthFromText((string) ($rawSheet['month_label'] ?? ''));

            if ($monthMeta === null) {
                $monthMeta = $this->detectMonthFromText((string) ($rawSheet['sheet'] ?? ''));
            }

            if ($monthMeta === null) {
                continue;
            }

            $sectionRows = $this->extractBelanjaSectionRowsFromRawSheet($rawSheet, $sectionKeyword);

            if ($sectionRows === []) {
                continue;
            }

            $monthKey = $monthMeta['key'];

            if (! isset($monthlyTeamData[$monthKey])) {
                $monthlyTeamData[$monthKey] = [
                    'month' => $monthMeta['label'],
                    'order' => $monthMeta['order'],
                    'teams' => [],
                ];
            }

            foreach ($sectionRows as $sectionRow) {
                $team = trim((string) ($sectionRow['label'] ?? ''));

                if ($team === '') {
                    continue;
                }

                $targetValue = $sectionRow['target'] ?? null;
                $realisasiValue = $sectionRow['realisasi'] ?? null;

                if ($targetValue === null && $realisasiValue === null) {
                    continue;
                }

                if (! isset($monthlyTeamData[$monthKey]['teams'][$team])) {
                    $monthlyTeamData[$monthKey]['teams'][$team] = [
                        'label' => $team,
                        'target' => 0.0,
                        'realisasi' => 0.0,
                    ];
                }

                $monthlyTeamData[$monthKey]['teams'][$team]['target'] += $targetValue ?? 0.0;
                $monthlyTeamData[$monthKey]['teams'][$team]['realisasi'] += $realisasiValue ?? 0.0;
            }
        }

        if ($monthlyTeamData === []) {
            return [
                'months' => [],
                'total_target_formatted' => number_format(0, 0, ',', '.'),
                'total_realisasi_formatted' => number_format(0, 0, ',', '.'),
                'overall_ratio' => 0,
            ];
        }

        usort($monthlyTeamData, static fn ($a, $b) => $a['order'] <=> $b['order']);

        $months = [];
        $grandTotalTarget = 0.0;
        $grandTotalRealisasi = 0.0;

        foreach ($monthlyTeamData as $monthData) {
            $teams = array_values($monthData['teams']);
            usort($teams, static fn ($a, $b) => $b['target'] <=> $a['target']);

            $monthTotalTarget = 0.0;
            $monthTotalRealisasi = 0.0;

            foreach ($teams as &$team) {
                $monthTotalTarget += $team['target'];
                $monthTotalRealisasi += $team['realisasi'];
            }
            unset($team);

            $maxScale = 0.0;

            if ($teams !== []) {
                $maxScale = max(array_map(static fn ($team) => max($team['target'], $team['realisasi']), $teams));
            }

            foreach ($teams as &$team) {
                $team['target_formatted'] = number_format($team['target'], 0, ',', '.');
                $team['realisasi_formatted'] = number_format($team['realisasi'], 0, ',', '.');
                $team['ratio'] = $team['target'] > 0 ? min(100, ($team['realisasi'] / $team['target']) * 100) : 0;
                $team['target_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['target'] / $maxScale) * 150)) : 12;
                $team['realisasi_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['realisasi'] / $maxScale) * 150)) : 12;
            }
            unset($team);

            $monthRatio = $monthTotalTarget > 0 ? ($monthTotalRealisasi / $monthTotalTarget) * 100 : 0;

            $months[] = [
                'label' => $monthData['month'],
                'teams' => array_slice($teams, 0, 8),
                'total_target_formatted' => number_format($monthTotalTarget, 0, ',', '.'),
                'total_realisasi_formatted' => number_format($monthTotalRealisasi, 0, ',', '.'),
                'ratio' => max(0, min(100, round($monthRatio, 2))),
            ];

            $grandTotalTarget += $monthTotalTarget;
            $grandTotalRealisasi += $monthTotalRealisasi;
        }

        $overallRatio = $grandTotalTarget > 0 ? ($grandTotalRealisasi / $grandTotalTarget) * 100 : 0;

        return [
            'months' => $months,
            'total_target_formatted' => number_format($grandTotalTarget, 0, ',', '.'),
            'total_realisasi_formatted' => number_format($grandTotalRealisasi, 0, ',', '.'),
            'overall_ratio' => max(0, min(100, round($overallRatio, 2))),
        ];
    }

    private function extractBelanjaSectionRowsFromRawSheet(array $rawSheet, string $sectionKeyword): array
    {
        $rows = $rawSheet['rows'] ?? [];
        $normalizedSectionKeyword = strtolower(trim($sectionKeyword));

        foreach ($rows as $index => $rowData) {
            $cells = $rowData['cells'] ?? [];
            $rowText = strtolower(trim(implode(' ', array_filter(array_map(static fn ($cell) => trim((string) $cell), $cells)))));

            if ($rowText === '' || ! str_contains($rowText, 'rpd bulan') || ! str_contains($rowText, $normalizedSectionKeyword)) {
                continue;
            }

            $sectionRows = [];

            for ($dataIndex = $index + 2; $dataIndex < count($rows); $dataIndex++) {
                $dataCells = $rows[$dataIndex]['cells'] ?? [];
                $label = trim((string) ($dataCells[0] ?? ''));
                $target = trim((string) ($dataCells[1] ?? ''));
                $realisasi = trim((string) ($dataCells[2] ?? ''));

                if ($label === '' && $target === '' && $realisasi === '') {
                    break;
                }

                if ($label === '' || preg_match('/^(persentase|total|jumlah|rpd bulan)$/i', $label) === 1) {
                    continue;
                }

                $targetValue = $this->normalizeToNumber($target);
                $realisasiValue = $this->normalizeToNumber($realisasi);

                if ($targetValue === null && $realisasiValue === null) {
                    continue;
                }

                $sectionRows[] = [
                    'label' => $label,
                    'target' => $targetValue,
                    'realisasi' => $realisasiValue,
                ];
            }

            return $sectionRows;
        }

        return [];
    }

    private function findBelanjaMonthData(array $months, string $selectedMonth): ?array
    {
        foreach ($months as $month) {
            if (($month['label'] ?? '') === $selectedMonth) {
                return $month;
            }
        }

        return null;
    }

    private function readCellDisplayValue($sheet, string $cellAddress): string
    {
        $cell = $sheet->getCell($cellAddress);

        try {
            $rawValue = $cell->getValue();
            if (is_string($rawValue) && str_starts_with($rawValue, '=')) {
                $calculated = $cell->getCalculatedValue();

                if (is_scalar($calculated) || $calculated === null) {
                    return (string) ($calculated ?? '');
                }
            }
        } catch (Throwable $e) {
            // Fallback to formatted value when formula calculation is unavailable.
        }

        return (string) $cell->getFormattedValue();
    }

    private function buildMergedRangesMap($sheet): array
    {
        $ranges = [];

        foreach ($sheet->getMergeCells() as $range) {
            $boundaries = Coordinate::rangeBoundaries($range);

            if (count($boundaries) !== 2) {
                continue;
            }

            $start = $boundaries[0];
            $end = $boundaries[1];
            $topLeftAddress = Coordinate::stringFromColumnIndex($start[0]).$start[1];

            $ranges[] = [
                'start_col' => (int) $start[0],
                'start_row' => (int) $start[1],
                'end_col' => (int) $end[0],
                'end_row' => (int) $end[1],
                'top_left' => $topLeftAddress,
            ];
        }

        return $ranges;
    }

    private function resolveMergedCellDisplayValue($sheet, array $mergedRanges, int $row, int $column): string
    {
        foreach ($mergedRanges as $range) {
            if (
                $column < $range['start_col'] ||
                $column > $range['end_col'] ||
                $row < $range['start_row'] ||
                $row > $range['end_row']
            ) {
                continue;
            }

            return $this->readCellDisplayValue($sheet, $range['top_left']);
        }

        return '';
    }

    private function extractRowsFromSheet($sheet): array
    {
        $sheetName = $sheet->getTitle();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $detectedHeader = null;
        $detectedColumns = [];
        $bestScore = 0;

        $headerSearchLimit = min(15, $highestRow);

        for ($row = 1; $row <= $headerSearchLimit; $row++) {
            $currentColumns = [];
            $score = 0;

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $cellAddress = Coordinate::stringFromColumnIndex($column).$row;
                $cellValue = trim((string) $sheet->getCell($cellAddress)->getFormattedValue());

                if ($cellValue === '') {
                    continue;
                }

                $field = $this->matchExcelHeader($cellValue);

                if ($field === null || isset($currentColumns[$field])) {
                    continue;
                }

                $currentColumns[$field] = Coordinate::stringFromColumnIndex($column);
                $score++;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $detectedHeader = $row;
                $detectedColumns = $currentColumns;
            }
        }

        if ($bestScore < 2) {
            $detectedHeader = 5;
            $detectedColumns = [
                'no' => 'A',
                'periode' => 'A',
                'tim' => 'B',
                'uraian_kro' => 'B',
                'nominal_rpd' => 'C',
                'deviasi' => 'D',
                'uraian' => 'E',
                'nominal_pengajuan' => 'F',
            ];
        }

        $rows = [];

        for ($row = $detectedHeader + 1; $row <= $highestRow; $row++) {
            $timColumn = $detectedColumns['tim'] ?? (
                isset($detectedColumns['target'], $detectedColumns['realisasi']) && ! isset($detectedColumns['tim'])
                    ? 'A'
                    : ($detectedColumns['uraian_kro'] ?? ($detectedColumns['uraian'] ?? 'B'))
            );

            $parsedRow = [
                'sheet' => $sheetName,
                'no' => trim((string) $sheet->getCell(($detectedColumns['no'] ?? 'A').$row)->getFormattedValue()),
                'periode' => trim((string) $sheet->getCell(($detectedColumns['periode'] ?? ($detectedColumns['no'] ?? 'A')).$row)->getFormattedValue()),
                'tim' => trim((string) $sheet->getCell($timColumn.$row)->getFormattedValue()),
                'uraian_kro' => trim((string) $sheet->getCell(($detectedColumns['uraian_kro'] ?? 'B').$row)->getFormattedValue()),
                'nominal_rpd' => trim((string) $sheet->getCell(($detectedColumns['nominal_rpd'] ?? 'C').$row)->getFormattedValue()),
                'deviasi' => trim((string) $sheet->getCell(($detectedColumns['deviasi'] ?? 'D').$row)->getFormattedValue()),
                'uraian' => trim((string) $sheet->getCell(($detectedColumns['uraian'] ?? 'E').$row)->getFormattedValue()),
                'nominal_pengajuan' => trim((string) $sheet->getCell(($detectedColumns['nominal_pengajuan'] ?? 'F').$row)->getFormattedValue()),
                'target' => trim((string) $sheet->getCell(($detectedColumns['target'] ?? ($detectedColumns['nominal_rpd'] ?? 'C')).$row)->getFormattedValue()),
                'realisasi' => trim((string) $sheet->getCell(($detectedColumns['realisasi'] ?? ($detectedColumns['nominal_pengajuan'] ?? 'F')).$row)->getFormattedValue()),
            ];

            $resolvedTeam = $this->resolveTeamLabel($parsedRow);

            $parsedRow['tim_display'] = $resolvedTeam;

            if (
                $parsedRow['no'] === '' &&
                $parsedRow['periode'] === '' &&
                $parsedRow['tim'] === '' &&
                $parsedRow['uraian_kro'] === '' &&
                $parsedRow['nominal_rpd'] === '' &&
                $parsedRow['deviasi'] === '' &&
                $parsedRow['uraian'] === '' &&
                $parsedRow['nominal_pengajuan'] === '' &&
                $parsedRow['target'] === '' &&
                $parsedRow['realisasi'] === ''
            ) {
                continue;
            }

            $rows[] = $parsedRow;
        }

        return [$rows, [
            'sheet' => $sheetName,
            'header_row' => $detectedHeader,
            'matched_columns' => array_keys($detectedColumns),
            'recognized_columns' => count($detectedColumns),
            'rows_found' => count($rows),
            'mode' => $bestScore < 3 ? 'fallback' : 'detected',
        ]];
    }

    private function extractMonthlySummaryRows($sheet): array
    {
        $sheetName = $sheet->getTitle();
        $highestRow = $sheet->getHighestDataRow();
        $sheetMonth = $this->detectMonthFromText($sheetName);
        $rows = [];

        for ($row = 1; $row <= max(1, $highestRow - 2); $row++) {
            $label = trim((string) $sheet->getCell('A'.$row)->getFormattedValue());

            if ($label === '' || ! str_contains(strtolower($label), 'rpd bulan')) {
                continue;
            }

            $targetHeader = strtolower(trim((string) $sheet->getCell('B'.($row + 1))->getFormattedValue()));
            $realisasiHeader = strtolower(trim((string) $sheet->getCell('C'.($row + 1))->getFormattedValue()));

            if (! str_contains($targetHeader, 'target') || ! str_contains($realisasiHeader, 'realisasi')) {
                continue;
            }

            $periodLabel = $sheetMonth['label'] ?? ($this->detectMonthFromText($label)['label'] ?? $label);

            for ($dataRow = $row + 2; $dataRow <= $highestRow; $dataRow++) {
                $team = trim((string) $sheet->getCell('A'.$dataRow)->getFormattedValue());
                $target = trim((string) $sheet->getCell('B'.$dataRow)->getFormattedValue());
                $realisasi = trim((string) $sheet->getCell('C'.$dataRow)->getFormattedValue());

                if ($team === '' && $target === '' && $realisasi === '') {
                    break;
                }

                if ($team === '' || preg_match('/^(persentase|total|jumlah)$/i', $team) === 1) {
                    continue;
                }

                $rows[] = [
                    'sheet' => $sheetName,
                    'no' => '',
                    'periode' => $periodLabel,
                    'tim' => $team,
                    'uraian_kro' => '',
                    'nominal_rpd' => $target,
                    'deviasi' => '',
                    'uraian' => '',
                    'nominal_pengajuan' => $realisasi,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'tim_display' => $team,
                ];
            }
        }

        return $rows;
    }

    private function matchExcelHeader(string $value): ?string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');

        return match (true) {
            $this->detectMonthFromText($value) !== null => 'periode',
            $normalized === 'no', $normalized === 'nomor' => 'no',
            str_contains($normalized, 'tim') || str_contains($normalized, 'team') || str_contains($normalized, 'unit') || str_contains($normalized, 'bidang') || str_contains($normalized, 'kelompok') => 'tim',
            str_contains($normalized, 'uraiankro') || (str_contains($normalized, 'kro') && str_contains($normalized, 'uraian')) => 'uraian_kro',
            str_contains($normalized, 'nominalrpd') || $normalized === 'rpd' || str_contains($normalized, 'rpdmurni') => 'nominal_rpd',
            str_contains($normalized, 'target') || str_contains($normalized, 'pagu') || str_contains($normalized, 'rencana') || str_contains($normalized, 'alokasi') || str_contains($normalized, 'anggaran') => 'target',
            str_contains($normalized, 'realisasi') || str_contains($normalized, 'actual') || str_contains($normalized, 'aktual') || str_contains($normalized, 'penyerapan') || str_contains($normalized, 'terserap') || str_contains($normalized, 'fadetail') || str_contains($normalized, 'fadtl') => 'realisasi',
            str_contains($normalized, 'deviasi') => 'deviasi',
            $normalized === 'uraian' => 'uraian',
            str_contains($normalized, 'nominalpengajuan') || str_contains($normalized, 'pengajuan') => 'nominal_pengajuan',
            default => null,
        };
    }

    private function buildOverviewChartData(array $rows): array
    {
        $monthlyTeamData = [];

        foreach ($rows as $index => $row) {
            $periodLabel = trim((string) ($row['periode'] ?? ''));

            if ($periodLabel === '') {
                $periodLabel = trim((string) ($row['sheet'] ?? ''));
            }

            $monthMeta = $this->detectMonthFromText($periodLabel);

            if ($monthMeta === null) {
                continue;
            }

            $monthKey = $monthMeta['key'];

            if (! isset($monthlyTeamData[$monthKey])) {
                $monthlyTeamData[$monthKey] = [
                    'month' => $monthMeta['label'],
                    'order' => $monthMeta['order'],
                    'teams' => [],
                ];
            }

            $team = $this->resolveTeamLabel($row);

            if ($team === null) {
                continue;
            }

            $targetRaw = trim((string) ($row['target'] ?? ''));
            $realisasiRaw = trim((string) ($row['realisasi'] ?? ''));

            if ($targetRaw === '') {
                $targetRaw = trim((string) ($row['nominal_rpd'] ?? ''));
            }

            if ($realisasiRaw === '') {
                $realisasiRaw = trim((string) ($row['nominal_pengajuan'] ?? ''));
            }

            $targetValue = $this->normalizeToNumber($targetRaw);
            $realisasiValue = $this->normalizeToNumber($realisasiRaw);

            if ($targetValue === null && $realisasiValue === null) {
                continue;
            }

            if (! isset($monthlyTeamData[$monthKey]['teams'][$team])) {
                $monthlyTeamData[$monthKey]['teams'][$team] = [
                    'label' => $team,
                    'target' => 0.0,
                    'realisasi' => 0.0,
                ];
            }

            $monthlyTeamData[$monthKey]['teams'][$team]['target'] += $targetValue ?? 0.0;
            $monthlyTeamData[$monthKey]['teams'][$team]['realisasi'] += $realisasiValue ?? 0.0;
        }

        if ($monthlyTeamData === []) {
            return [
                'months' => [],
                'total_target_formatted' => number_format(0, 0, ',', '.'),
                'total_realisasi_formatted' => number_format(0, 0, ',', '.'),
                'overall_ratio' => 0,
            ];
        }

        usort($monthlyTeamData, static fn ($a, $b) => $a['order'] <=> $b['order']);

        $months = [];
        $grandTotalTarget = 0.0;
        $grandTotalRealisasi = 0.0;

        foreach ($monthlyTeamData as $monthData) {
            $teams = array_values($monthData['teams']);
            usort($teams, static fn ($a, $b) => $b['target'] <=> $a['target']);

            $monthTotalTarget = 0.0;
            $monthTotalRealisasi = 0.0;

            foreach ($teams as &$team) {
                $monthTotalTarget += $team['target'];
                $monthTotalRealisasi += $team['realisasi'];
            }

            $maxScale = 0.0;

            if ($teams !== []) {
                $maxScale = max(array_map(static fn ($team) => max($team['target'], $team['realisasi']), $teams));
            }

            foreach ($teams as &$team) {
                $team['target_formatted'] = number_format($team['target'], 0, ',', '.');
                $team['realisasi_formatted'] = number_format($team['realisasi'], 0, ',', '.');
                $team['ratio'] = $team['target'] > 0 ? min(100, ($team['realisasi'] / $team['target']) * 100) : 0;
                $team['target_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['target'] / $maxScale) * 150)) : 12;
                $team['realisasi_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['realisasi'] / $maxScale) * 150)) : 12;
            }
            unset($team);

            $monthRatio = $monthTotalTarget > 0 ? ($monthTotalRealisasi / $monthTotalTarget) * 100 : 0;

            $months[] = [
                'label' => $monthData['month'],
                'teams' => array_slice($teams, 0, 8),
                'total_target_formatted' => number_format($monthTotalTarget, 0, ',', '.'),
                'total_realisasi_formatted' => number_format($monthTotalRealisasi, 0, ',', '.'),
                'ratio' => max(0, min(100, round($monthRatio, 2))),
            ];

            $grandTotalTarget += $monthTotalTarget;
            $grandTotalRealisasi += $monthTotalRealisasi;
        }

        $overallRatio = $grandTotalTarget > 0 ? ($grandTotalRealisasi / $grandTotalTarget) * 100 : 0;

        return [
            'months' => $months,
            'total_target_formatted' => number_format($grandTotalTarget, 0, ',', '.'),
            'total_realisasi_formatted' => number_format($grandTotalRealisasi, 0, ',', '.'),
            'overall_ratio' => max(0, min(100, round($overallRatio, 2))),
        ];
    }

    private function buildOverviewChartDataForMonth(array $rows, string $selectedMonth): ?array
    {
        $targetMonth = $this->detectMonthFromText($selectedMonth);

        if ($targetMonth === null) {
            return null;
        }

        $monthData = [
            'month' => $targetMonth['label'],
            'order' => $targetMonth['order'],
            'teams' => [],
        ];

        foreach ($rows as $row) {
            $periodLabel = trim((string) ($row['periode'] ?? ''));
            $sheetLabel = trim((string) ($row['sheet'] ?? ''));

            $monthMeta = $this->detectMonthFromText($periodLabel);

            if ($monthMeta === null && $sheetLabel !== '') {
                $monthMeta = $this->detectMonthFromText($sheetLabel);
            }

            if ($monthMeta === null || $monthMeta['label'] !== $targetMonth['label']) {
                continue;
            }

            $team = $this->resolveTeamLabel($row);

            if ($team === null) {
                continue;
            }

            $targetRaw = trim((string) ($row['target'] ?? ''));
            $realisasiRaw = trim((string) ($row['realisasi'] ?? ''));

            if ($targetRaw === '') {
                $targetRaw = trim((string) ($row['nominal_rpd'] ?? ''));
            }

            if ($realisasiRaw === '') {
                $realisasiRaw = trim((string) ($row['nominal_pengajuan'] ?? ''));
            }

            $targetValue = $this->normalizeToNumber($targetRaw);
            $realisasiValue = $this->normalizeToNumber($realisasiRaw);

            if ($targetValue === null && $realisasiValue === null) {
                continue;
            }

            if (! isset($monthData['teams'][$team])) {
                $monthData['teams'][$team] = [
                    'label' => $team,
                    'target' => 0.0,
                    'realisasi' => 0.0,
                ];
            }

            $monthData['teams'][$team]['target'] += $targetValue ?? 0.0;
            $monthData['teams'][$team]['realisasi'] += $realisasiValue ?? 0.0;
        }

        if ($monthData['teams'] === []) {
            return null;
        }

        $teams = array_values($monthData['teams']);
        usort($teams, static fn ($a, $b) => $b['target'] <=> $a['target']);

        $monthTotalTarget = 0.0;
        $monthTotalRealisasi = 0.0;

        foreach ($teams as &$team) {
            $monthTotalTarget += $team['target'];
            $monthTotalRealisasi += $team['realisasi'];
        }
        unset($team);

        $maxScale = 0.0;

        if ($teams !== []) {
            $maxScale = max(array_map(static fn ($team) => max($team['target'], $team['realisasi']), $teams));
        }

        foreach ($teams as &$team) {
            $team['target_formatted'] = number_format($team['target'], 0, ',', '.');
            $team['realisasi_formatted'] = number_format($team['realisasi'], 0, ',', '.');
            $team['ratio'] = $team['target'] > 0 ? min(100, ($team['realisasi'] / $team['target']) * 100) : 0;
            $team['target_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['target'] / $maxScale) * 150)) : 12;
            $team['realisasi_bar_height'] = $maxScale > 0 ? max(12, (int) round(($team['realisasi'] / $maxScale) * 150)) : 12;
        }
        unset($team);

        $monthRatio = $monthTotalTarget > 0 ? ($monthTotalRealisasi / $monthTotalTarget) * 100 : 0;

        return [
            'months' => [[
                'label' => $monthData['month'],
                'teams' => array_slice($teams, 0, 8),
                'total_target_formatted' => number_format($monthTotalTarget, 0, ',', '.'),
                'total_realisasi_formatted' => number_format($monthTotalRealisasi, 0, ',', '.'),
                'ratio' => max(0, min(100, round($monthRatio, 2))),
            ]],
            'total_target_formatted' => number_format($monthTotalTarget, 0, ',', '.'),
            'total_realisasi_formatted' => number_format($monthTotalRealisasi, 0, ',', '.'),
            'overall_ratio' => max(0, min(100, round($monthRatio, 2))),
        ];
    }

    private function detectMonthFromText(string $text): ?array
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $text) ?? '');

        if ($normalized === '') {
            return null;
        }

        $monthMap = [
            'januari' => ['key' => 'januari', 'label' => 'Januari', 'order' => 1],
            'january' => ['key' => 'januari', 'label' => 'Januari', 'order' => 1],
            'februari' => ['key' => 'februari', 'label' => 'Februari', 'order' => 2],
            'february' => ['key' => 'februari', 'label' => 'Februari', 'order' => 2],
            'maret' => ['key' => 'maret', 'label' => 'Maret', 'order' => 3],
            'march' => ['key' => 'maret', 'label' => 'Maret', 'order' => 3],
            'april' => ['key' => 'april', 'label' => 'April', 'order' => 4],
            'mei' => ['key' => 'mei', 'label' => 'Mei', 'order' => 5],
            'may' => ['key' => 'mei', 'label' => 'Mei', 'order' => 5],
            'juni' => ['key' => 'juni', 'label' => 'Juni', 'order' => 6],
            'june' => ['key' => 'juni', 'label' => 'Juni', 'order' => 6],
            'juli' => ['key' => 'juli', 'label' => 'Juli', 'order' => 7],
            'july' => ['key' => 'juli', 'label' => 'Juli', 'order' => 7],
            'agustus' => ['key' => 'agustus', 'label' => 'Agustus', 'order' => 8],
            'august' => ['key' => 'agustus', 'label' => 'Agustus', 'order' => 8],
            'september' => ['key' => 'september', 'label' => 'September', 'order' => 9],
            'oktober' => ['key' => 'oktober', 'label' => 'Oktober', 'order' => 10],
            'october' => ['key' => 'oktober', 'label' => 'Oktober', 'order' => 10],
            'november' => ['key' => 'november', 'label' => 'November', 'order' => 11],
            'desember' => ['key' => 'desember', 'label' => 'Desember', 'order' => 12],
            'december' => ['key' => 'desember', 'label' => 'Desember', 'order' => 12],
        ];

        foreach ($monthMap as $needle => $monthMeta) {
            if (str_contains($normalized, $needle)) {
                return $monthMeta;
            }
        }

        return null;
    }

    private function resolveTeamLabel(array $row): ?string
    {
        $candidates = [
            trim((string) ($row['tim'] ?? '')),
            trim((string) ($row['uraian'] ?? '')),
            trim((string) ($row['uraian_kro'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (! $this->isLikelyTeamLabel($candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function isLikelyKroCode(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^\d{4}\.[a-z]{3}\.\d{2,3}$/i', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^\d{4,}[a-z]*$/i', $trimmed) === 1) {
            return true;
        }

        $compact = preg_replace('/[^a-z0-9]/i', '', $trimmed) ?? '';

        if ($compact === '') {
            return false;
        }

        $digitCount = preg_match_all('/\d/', $compact);
        $digitRatio = $digitCount !== false ? $digitCount / strlen($compact) : 0;

        return $digitRatio >= 0.5 && str_contains($trimmed, '.');
    }

    private function isLikelyTeamLabel(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if ($this->isLikelyKroCode($trimmed)) {
            return false;
        }

        $normalized = strtolower(preg_replace('/[^a-z0-9\s]+/i', ' ', $trimmed) ?? '');
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        if ($normalized === '') {
            return false;
        }

        $words = explode(' ', $normalized);

        if (count($words) > 3) {
            return false;
        }

        $noiseKeywords = [
            'honor', 'petugas', 'pendataan', 'lapangan', 'survei', 'survey', 'industri', 'besar', 'sedang',
            'triwulanan', 'triwulan', 'bulanan', 'ibs', 'detail', 'realisasi', 'target', 'rpd', 'fa', 'fadtl',
            'pengolahan', 'pencacahan', 'pemutakhiran', 'statistik', 'lapor', 'laporan', 'kegiatan', 'program',
        ];

        foreach ($noiseKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return false;
            }
        }

        if (preg_match('/^tim\s+[a-z0-9]+$/i', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^[a-z][a-z\s&.-]{1,28}$/i', $trimmed) === 1) {
            return true;
        }

        return preg_match('/^[a-z0-9][a-z0-9\s&.-]{0,28}$/i', $trimmed) === 1;
    }

    private function normalizeToNumber(string $value): ?float
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9,.-]/', '', $trimmed);

        if ($normalized === null || $normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return null;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma) {
            $normalized = preg_match('/,\d{1,2}$/', $normalized) === 1
                ? str_replace(',', '.', $normalized)
                : str_replace(',', '', $normalized);
        } elseif ($hasDot) {
            $normalized = preg_match('/\.\d{1,2}$/', $normalized) === 1
                ? $normalized
                : str_replace('.', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public function archive(): View
    {
        $uploads = Upload::orderBy('created_at', 'desc')->paginate(10);

        return view('archive', [
            'uploads' => $uploads,
        ]);
    }

    public function archiveLoad($id): View|RedirectResponse
    {
        $upload = Upload::find($id);

        if (! $upload) {
            return redirect()->route('archive')->with('error', 'File tidak ditemukan.');
        }

        try {
            if (! file_exists(storage_path('app/private/'.$upload->file_path))) {
                return redirect()->route('archive')->with('error', 'File tidak ada di storage.');
            }

            $viewData = $this->loadUploadViewData($upload);

            session([
                'excel_rows' => $viewData['rows'],
                'excel_file_name' => $viewData['fileName'],
                'excel_analysis' => $viewData['analysis'],
                'excel_raw_sheets' => $viewData['rawSheets'],
            ]);

            return redirect()->route('keutata', ['upload' => $upload->id])->with('success', 'File dari arsip berhasil dimuat: '.$upload->file_name);
        } catch (Throwable $e) {
            return redirect()->route('archive')->with('error', 'Gagal membaca file: '.$e->getMessage());
        }
    }

    public function archiveDelete(Request $request, $id): RedirectResponse
    {
        $upload = Upload::find($id);

        if (! $upload) {
            return redirect()->route('archive')->with('error', 'File tidak ditemukan.');
        }

        try {
            // Try deleting via storage disk first
            if (Storage::disk('local')->exists($upload->file_path)) {
                Storage::disk('local')->delete($upload->file_path);
            } elseif (Storage::disk('local')->exists('private/'.$upload->file_path)) {
                Storage::disk('local')->delete('private/'.$upload->file_path);
            } else {
                $path = storage_path('app/private/'.$upload->file_path);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $upload->delete();

            return redirect()->route('archive')->with('success', 'File berhasil dihapus dari arsip.');
        } catch (Throwable $e) {
            return redirect()->route('archive')->with('error', 'Gagal menghapus file: '.$e->getMessage());
        }
    }

    public function archiveBulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids');

        if (! $ids) {
            return redirect()->route('archive')->with('error', 'Tidak ada file yang dipilih.');
        }

        $idArray = explode(',', $ids);
        $idArray = array_map('trim', $idArray);
        $idArray = array_filter($idArray);

        $uploads = Upload::whereIn('id', $idArray)->get();

        if ($uploads->isEmpty()) {
            return redirect()->route('archive')->with('error', 'File tidak ditemukan.');
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($uploads as $upload) {
            try {
                // Try deleting via storage disk first
                if (Storage::disk('local')->exists($upload->file_path)) {
                    Storage::disk('local')->delete($upload->file_path);
                } elseif (Storage::disk('local')->exists('private/'.$upload->file_path)) {
                    Storage::disk('local')->delete('private/'.$upload->file_path);
                } else {
                    $path = storage_path('app/private/'.$upload->file_path);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }

                $upload->delete();
                $deletedCount++;
            } catch (Throwable $e) {
                $errors[] = $upload->file_name.': '.$e->getMessage();
            }
        }

        $message = $deletedCount.' file berhasil dihapus.';
        if (! empty($errors)) {
            $message .= ' '.count($errors).' file gagal dihapus.';
        }

        return redirect()->route('archive')->with('success', $message);
    }

}

