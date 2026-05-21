<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Upload;

$upload = Upload::orderBy('created_at', 'desc')->first();
if (! $upload) {
    echo "No Upload found\n";
    exit(2);
}
$analysis = $upload->analysis_data ?? [];
echo "Upload id={$upload->id}\n";
echo "file_path={$upload->file_path}\n";
$spreadsheetId = $analysis['spreadsheet_id'] ?? '(none)';
echo "analysis.spreadsheet_id={$spreadsheetId}\n";

$credentialsPath = trim((string) config('services.google_sheets.credentials_path'));
echo "credentials_path={$credentialsPath}\n";
if (file_exists($credentialsPath)) {
    $json = json_decode(file_get_contents($credentialsPath), true);
    $clientEmail = $json['client_email'] ?? '(no client_email)';
    echo "credentials.client_email={$clientEmail}\n";
} else {
    echo "credentials file not found\n";
}
