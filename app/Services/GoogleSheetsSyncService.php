<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Upload;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class GoogleSheetsSyncService
{
    public function syncToUpload(): Upload
    {
        // Coba ambil dari database dulu, kalau tidak ada gunakan .env
        $dbSpreadsheetId = Setting::where('key', 'google_sheets_spreadsheet_id')->first()?->value;
        $spreadsheetId = trim((string) ($dbSpreadsheetId ?? config('services.google_sheets.spreadsheet_id')));
        $credentialsPath = trim((string) config('services.google_sheets.credentials_path'));

        if ($spreadsheetId === '') {
            throw new RuntimeException('GOOGLE_SHEETS_SPREADSHEET_ID belum diatur di .env.');
        }

        if ($credentialsPath === '' || ! file_exists($credentialsPath)) {
            throw new RuntimeException('File kredensial Google Sheets tidak ditemukan: '.$credentialsPath);
        }

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

        $sheetsService = new Sheets($client);
        
        try {
            $spreadsheetMeta = $sheetsService->spreadsheets->get($spreadsheetId);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 403) {
                throw new RuntimeException(
                    'Akses ditolak (403). Silakan share spreadsheet dengan email: '.
                    'monitoring-rpd-sync@monitoring-rpd.iam.gserviceaccount.com dengan permission Editor. '.
                    'Detail: '.$e->getMessage()
                );
            } elseif ($e->getCode() === 404) {
                throw new RuntimeException(
                    'Spreadsheet tidak ditemukan (404). Periksa kembali ID Spreadsheet. '.
                    'ID yang digunakan: '.$spreadsheetId
                );
            }
            throw $e;
        }
        
        $sheetEntries = $spreadsheetMeta->getSheets() ?? [];

        if ($sheetEntries === []) {
            throw new RuntimeException('Spreadsheet tidak memiliki sheet yang bisa dibaca.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $usedWorksheetTitles = [];

        foreach ($sheetEntries as $sheetEntry) {
            $properties = $sheetEntry->getProperties();
            $title = (string) ($properties?->getTitle() ?? 'Sheet1');
            $range = "'".str_replace("'", "''", $title)."'";

            $valueRange = $sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            $rows = $valueRange->getValues() ?? [];

            $worksheetTitle = $this->makeValidWorksheetTitle($title, $usedWorksheetTitles);
            $worksheet = new Worksheet($spreadsheet, $worksheetTitle);
            $spreadsheet->addSheet($worksheet);

            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $columnIndex => $cellValue) {
                    $cellAddress = Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1);
                    $worksheet->setCellValueExplicit($cellAddress, (string) $cellValue, DataType::TYPE_STRING);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $timestamp = now()->format('Ymd_His');
        $relativePath = 'excel_uploads/google_sheets_sync_'.$timestamp.'.xlsx';
        $absolutePath = storage_path('app/private/'.$relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($absolutePath);
        $spreadsheet->disconnectWorksheets();

        $loadedSpreadsheet = IOFactory::load($absolutePath);
        $rowsCount = 0;

        foreach ($loadedSpreadsheet->getWorksheetIterator() as $worksheet) {
            $rowsCount += max(0, (int) $worksheet->getHighestDataRow());
        }

        return Upload::create([
            'file_name' => 'Google Sheets Sync '.$timestamp,
            'file_path' => $relativePath,
            'rows_count' => $rowsCount,
            'analysis_data' => [
                'source' => 'google_sheets',
                'spreadsheet_id' => $spreadsheetId,
                'synced_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    private function makeValidWorksheetTitle(string $title, array &$usedTitles): string
    {
        $sanitized = str_replace(['*', ':', '/', '\\', '?', '[', ']'], ' ', $title);
        $sanitized = trim(preg_replace('/\s+/', ' ', $sanitized) ?? '');

        if ($sanitized === '') {
            $sanitized = 'Sheet';
        }

        $sanitized = mb_substr($sanitized, 0, 31);
        $candidate = $sanitized;
        $counter = 2;

        while (in_array($candidate, $usedTitles, true)) {
            $suffix = ' '.$counter;
            $baseLength = max(1, 31 - mb_strlen($suffix));
            $candidate = mb_substr($sanitized, 0, $baseLength).$suffix;
            $counter++;
        }

        $usedTitles[] = $candidate;

        return $candidate;
    }
}
