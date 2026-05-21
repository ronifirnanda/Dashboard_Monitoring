<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Upload;

$uploadId = 346;
$sheetName = 'April 2026';

$upload = Upload::find($uploadId);
if (! $upload) {
    echo "Upload id={$uploadId} not found\n";
    exit(2);
}
$d = $upload->analysis_data;
if (is_string($d)) {
    $decoded = json_decode($d, true);
    $d = is_array($decoded) ? $decoded : [];
}
if (! is_array($d)) {
    $d = [];
}
$d['sheet_name'] = $sheetName;
$upload->analysis_data = $d;
$upload->save();

echo "Updated Upload id={$uploadId} sheet_name to '{$sheetName}'\n";
