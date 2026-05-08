<?php

use App\Services\GoogleSheetsSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sheets:sync', function (GoogleSheetsSyncService $sheetsSyncService) {
    $upload = $sheetsSyncService->syncToUpload();
    $this->info('Google Sheets berhasil disinkronkan.');
    $this->line('Upload ID: '.$upload->id);
    $this->line('File: '.$upload->file_name);
    $this->line('Rows: '.$upload->rows_count);
})->purpose('Sinkronisasi data dari Google Sheets private ke upload lokal');
