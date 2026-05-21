<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Upload;
use Google\Client;
use Google\Service\Sheets;

$upload = Upload::orderBy('created_at', 'desc')->first();
if (! $upload) {
    echo "No Upload found\n";
    exit(2);
}
$analysis = $upload->analysis_data ?? [];
$spreadsheetId = $analysis['spreadsheet_id'] ?? null;
if (! $spreadsheetId) {
    echo "No spreadsheet_id in Upload.analysis_data\n";
    exit(3);
}

$credentialsPath = trim((string) config('services.google_sheets.credentials_path'));
if (! file_exists($credentialsPath)) {
    echo "Credentials file not found at: $credentialsPath\n";
    exit(4);
}

$client = new Client();
$client->setAuthConfig($credentialsPath);
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$sheetsService = new Sheets($client);

try {
    $meta = $sheetsService->spreadsheets->get($spreadsheetId);
    $sheets = $meta->getSheets() ?? [];
    echo "Spreadsheet ID: $spreadsheetId\n";
    echo "Sheets:\n";
    foreach ($sheets as $s) {
        $title = $s->getProperties()->getTitle() ?? '(no title)';
        echo " - $title\n";
    }
} catch (\Exception $e) {
    echo "Error fetching spreadsheet metadata: ". $e->getMessage() ."\n";
    exit(5);
}
