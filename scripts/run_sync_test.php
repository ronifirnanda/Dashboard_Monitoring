<?php

require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Upload;
use App\Services\GoogleSheetsSyncService;

echo "Starting Google Sheets sync test...\n";

$upload = Upload::orderBy('created_at', 'desc')->first();
if (! $upload) {
    echo "No Upload records found.\n";
    exit(2);
}

$analysis = $upload->analysis_data ?? [];
echo "Found Upload id={$upload->id}, file_path={$upload->file_path}\n";
if (isset($analysis['source'])) {
    echo "analysis_data.source={$analysis['source']}\n";
}
if (! isset($analysis['spreadsheet_id']) || trim((string)$analysis['spreadsheet_id']) === '') {
    echo "No spreadsheet_id found in analysis_data. Aborting sync test.\n";
    exit(3);
}

$sheetName = array_key_exists('sheet_name', $analysis) ? $analysis['sheet_name'] : 'Sheet1';
$cell = 'A1';
$value = 'TEST_SYNC '.date('c');

$service = new GoogleSheetsSyncService();

try {
    $service->updateCellInSpreadsheet($upload, $sheetName, $cell, $value);
    echo "Sync test: updateCellInSpreadsheet completed successfully.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Sync test error: " . $e->getMessage() . "\n";
    // Print nested exceptions if any
    $prev = $e->getPrevious();
    if ($prev) {
        echo "Previous: " . $prev->getMessage() . "\n";
    }
    exit(4);
}
