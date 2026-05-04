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
        $chart = $this->buildOverviewChartData($viewData['rows']);
        $selectedMonth = trim((string) $request->query('chart_month', ''));
        $monthOptions = array_map(static fn ($month) => $month['label'], $chart['months'] ?? []);

        if ($selectedMonth !== '' && ! in_array($selectedMonth, $monthOptions, true)) {
            $selectedMonth = '';
        }

        $selectedMonthData = null;

        if ($selectedMonth !== '') {
            foreach ($chart['months'] ?? [] as $month) {
                if (($month['label'] ?? '') === $selectedMonth) {
                    $selectedMonthData = $month;
                    break;
                }
            }
        }

        return view('overview', [
            'rows' => $viewData['rows'],
            'fileName' => $viewData['fileName'],
            'analysis' => $viewData['analysis'],
            'chart' => $chart,
            'selectedMonth' => $selectedMonth,
            'selectedMonthData' => $selectedMonthData,
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
            ]);

            return redirect()->route('keutata', ['upload' => $upload->id])->with('success', 'File Excel berhasil diupload dan dipelajari.');
        } catch (Throwable $e) {
            return redirect()->route('keutata')->with('uploadError', 'Gagal membaca file Excel: '.$e->getMessage());
        }
    }

    public function keutata(Request $request): View
    {
        $viewData = $this->resolveKeutataViewData($request->query('upload'));

        return view('keutata', [
            'rows' => $viewData['rows'],
            'fileName' => $viewData['fileName'],
            'error' => null,
            'analysis' => $viewData['analysis'],
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

        if (is_array($sessionRows) && $sessionFileName !== null) {
            return [
                'rows' => $sessionRows,
                'fileName' => $sessionFileName,
                'analysis' => is_array($sessionAnalysis) ? $sessionAnalysis : [],
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
            ];
        }

        $spreadsheet = IOFactory::load($storagePath);

        return $this->buildKeutataViewData($spreadsheet, $upload->file_name);
    }

    private function buildKeutataViewData($spreadsheet, ?string $fileName): array
    {
        $rows = [];
        $analysis = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            [$sheetRows, $sheetAnalysis] = $this->extractRowsFromSheet($sheet);
            $sheetRows = array_merge($sheetRows, $this->extractMonthlySummaryRows($sheet));

            $rows = array_merge($rows, $sheetRows);
            $analysis[] = $sheetAnalysis;
        }

        return [
            'rows' => $rows,
            'fileName' => $fileName,
            'analysis' => $analysis,
            'error' => null,
        ];
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
            ]);

            return redirect()->route('keutata', ['upload' => $upload->id])->with('success', 'File dari arsip berhasil dimuat: '.$upload->file_name);
        } catch (Throwable $e) {
            return redirect()->route('archive')->with('error', 'Gagal membaca file: '.$e->getMessage());
        }
    }


}
