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
    public function updateCellInSpreadsheet(Upload $upload, string $sheetName, string $cellAddress, string $value): void
    {
        $spreadsheetId = trim((string) ($upload->analysis_data['spreadsheet_id'] ?? ''));

        if ($spreadsheetId === '') {
            throw new RuntimeException('Spreadsheet ID Google Sheets tidak tersedia untuk upload ini.');
        }
        // Ensure we resolve credentials path (same as sync flow) so client can be created reliably
        [$cfgSpreadsheetId, $credentialsPath] = $this->resolveConnectionConfig();

        if ($credentialsPath === '' || ! file_exists($credentialsPath)) {
            throw new RuntimeException('File kredensial Google Sheets tidak ditemukan saat mencoba update: '.$credentialsPath);
        }

        $client = $this->makeClient(false, $credentialsPath);
        $sheetsService = new Sheets($client);
        $range = "'".str_replace("'", "''", $sheetName)."'!".$cellAddress;

        $valueRange = new \Google\Service\Sheets\ValueRange([
            'range' => $range,
            'values' => [[ $value ]],
        ]);

        try {
            $sheetsService->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $valueRange,
                ['valueInputOption' => 'USER_ENTERED']
            );
        } catch (\Google\Service\Exception $e) {
            throw new RuntimeException('Gagal update ke Google Sheets: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function syncToUpload(): Upload
    {
        [$spreadsheetId, $credentialsPath] = $this->resolveConnectionConfig();

        if ($spreadsheetId === '') {
            throw new RuntimeException('GOOGLE_SHEETS_SPREADSHEET_ID belum diatur di .env.');
        }

        if ($credentialsPath === '' || ! file_exists($credentialsPath)) {
            throw new RuntimeException('File kredensial Google Sheets tidak ditemukan: '.$credentialsPath);
        }

        $client = $this->makeClient(true, $credentialsPath);

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

    private function makeClient(bool $readOnly = true, ?string $credentialsPath = null): Client
    {
        $resolvedCredentialsPath = trim((string) ($credentialsPath ?? config('services.google_sheets.credentials_path')));

        if ($resolvedCredentialsPath === '' || ! file_exists($resolvedCredentialsPath)) {
            throw new RuntimeException('File kredensial Google Sheets tidak ditemukan: '.$resolvedCredentialsPath);
        }

        $client = new Client();
        $client->setAuthConfig($resolvedCredentialsPath);
        $client->setScopes([
            $readOnly ? Sheets::SPREADSHEETS_READONLY : Sheets::SPREADSHEETS,
        ]);

        return $client;
    }

    private function resolveConnectionConfig(): array
    {
        $dbSpreadsheetId = Setting::where('key', 'google_sheets_spreadsheet_id')->first()?->value;
        $spreadsheetId = trim((string) ($dbSpreadsheetId ?? config('services.google_sheets.spreadsheet_id')));
        $credentialsPath = trim((string) config('services.google_sheets.credentials_path'));

        if ($spreadsheetId === '') {
            throw new RuntimeException('GOOGLE_SHEETS_SPREADSHEET_ID belum diatur di .env.');
        }

        if ($credentialsPath === '' || ! file_exists($credentialsPath)) {
            throw new RuntimeException('File kredensial Google Sheets tidak ditemukan: '.$credentialsPath);
        }

        return [$spreadsheetId, $credentialsPath];
    }

    private function makeValidWorksheetTitle(string $title, array &$usedTitles): string
    {
        // Remove control characters and other invalid characters for worksheet titles
        // Excel/PhpSpreadsheet disallow characters like : \ / ? * [ ] and control chars (0x00-0x1F, 0x7F)
        $sanitized = $title;
        // Replace common invalid characters with space
        $sanitized = str_replace(['*', ':', '/', '\\', '?', '[', ']'], ' ', $sanitized);
        // Remove non-printable/control characters
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $sanitized) ?? $sanitized;
        // Normalize whitespace
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
