<?php

namespace Tests\Feature;

use App\Models\Upload;
use App\Models\User;
use App\Services\GoogleSheetsSyncService;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;

class KeutataExcelUploadTest extends TestCase
{
    public function test_it_uploads_and_parses_the_latest_excel_file(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFile();

        try {
            // POST to upload endpoint (will redirect to keutata)
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            // Follow the redirect to keutata
            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');
            $keutataResponse = $this->get(route('keutata'));
            $this->assertSame(200, $keutataResponse->getStatusCode());

            $content = $keutataResponse->getContent();
            $this->assertStringContainsString('monitoring.xlsx', $content);
            $this->assertStringContainsString('Data A', $content);
            $this->assertStringContainsString('Uraian A', $content);
            $this->assertStringContainsString('struktur terdeteksi', $content);

            // Verify data is stored in session
            $this->assertEquals('monitoring.xlsx', session('excel_file_name'));
            $sessionRows = session('excel_rows');
            $this->assertIsArray($sessionRows);
            $this->assertCount(1, $sessionRows);

            // Verify overview also displays the data
            $overviewResponse = $this->get(route('overview'));
            $this->assertSame(200, $overviewResponse->getStatusCode());
            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Upload Excel Baru', $overviewContent);
            $this->assertStringContainsString('Data A', $overviewContent);
            $this->assertStringContainsString('Uraian A', $overviewContent);
            $this->assertStringContainsString('Data dari Excel', $overviewContent);
            $this->assertStringContainsString('Data bulanan belum ditemukan', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_shows_always_on_edit_ui_for_admin_users(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFile();

        try {
            $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])->assertStatus(302);

            $upload = Upload::query()->latest('id')->firstOrFail();
            $admin = User::factory()->create(['role' => 'admin']);

            $keutataResponse = $this->actingAs($admin)->get(route('keutata', ['upload' => $upload->id]));

            $keutataResponse->assertStatus(200);
            $content = $keutataResponse->getContent();

            $this->assertStringContainsString('Mode edit aktif.', $content);
            $this->assertStringContainsString('Simpan perubahan', $content);
            $this->assertStringContainsString('contenteditable="true"', $content);
            $this->assertStringNotContainsString('Matikan mode edit', $content);
            $this->assertStringNotContainsString('toggle-edit', $content);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_keeps_edit_mode_available_without_resync_on_next_keutata_open(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFile();

        try {
            $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])->assertStatus(302);

            $admin = User::factory()->create(['role' => 'admin']);

            $firstOpen = $this->actingAs($admin)->get(route('keutata'));
            $firstOpen->assertStatus(200);
            $this->assertStringContainsString('contenteditable="true"', $firstOpen->getContent());

            $secondOpen = $this->actingAs($admin)->get(route('keutata'));
            $secondOpen->assertStatus(200);
            $this->assertStringContainsString('contenteditable="true"', $secondOpen->getContent());
            $this->assertStringContainsString('Simpan perubahan', $secondOpen->getContent());
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_saves_keutata_cell_to_spreadsheet_and_syncs_google_sheet_when_configured(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFile();

        try {
            $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])->assertStatus(302);

            $upload = Upload::query()->latest('id')->firstOrFail();
            $upload->analysis_data = [
                'source' => 'google_sheets',
                'spreadsheet_id' => 'sheet-123',
            ];
            $upload->save();

            $syncService = Mockery::mock(GoogleSheetsSyncService::class);
            $uploadId = $upload->id;

            $syncService->shouldReceive('updateCellInSpreadsheet')
                ->once()
                ->withArgs(function (Upload $passedUpload, string $sheet, string $cell, string $value) use ($uploadId): bool {
                    return $passedUpload->id === $uploadId
                        && $sheet === 'Rekap'
                        && $cell === 'B3'
                        && $value === 'Data B';
                });
            $this->app->instance(GoogleSheetsSyncService::class, $syncService);

            $admin = User::factory()->create(['role' => 'admin']);

            $response = $this->actingAs($admin)->postJson(route('keutata.update-cell'), [
                'upload_id' => $upload->id,
                'sheet' => 'Rekap',
                'cell' => 'B3',
                'value' => 'Data B',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Sel berhasil diperbarui.',
                    'cell' => 'B3',
                    'value' => 'Data B',
                    'synced_to_google_sheet' => true,
                ]);

            $updatedSpreadsheet = IOFactory::load(storage_path('app/private/'.$upload->file_path));
            $this->assertSame('Data B', (string) $updatedSpreadsheet->getActiveSheet()->getCell('B3')->getValue());
        } finally {
            Mockery::close();

            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_preserves_thousand_separator_value_when_saving_keutata_cell(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFile();

        try {
            $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])->assertStatus(302);

            $upload = Upload::query()->latest('id')->firstOrFail();
            $admin = User::factory()->create(['role' => 'admin']);

            $response = $this->actingAs($admin)->postJson(route('keutata.update-cell'), [
                'upload_id' => $upload->id,
                'sheet' => 'Rekap',
                'cell' => 'C3',
                'value' => '300.000',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'cell' => 'C3',
                    'value' => '300.000',
                ]);

            $updatedSpreadsheet = IOFactory::load(storage_path('app/private/'.$upload->file_path));
            $worksheet = $updatedSpreadsheet->getSheetByName('Rekap');
            $this->assertNotNull($worksheet);
            $this->assertSame('300.000', (string) $worksheet->getCell('C3')->getValue());
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_builds_overview_chart_when_header_names_are_synonyms(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithSynonymHeaders();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-synonym.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview', ['chart_month' => 'Februari']));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Target vs Realisasi', $overviewContent);
            $this->assertStringContainsString('Februari', $overviewContent);
            $this->assertStringContainsString('PSS', $overviewContent);
            $this->assertStringContainsString('Distribusi', $overviewContent);
            $this->assertStringContainsString('17.008.000', $overviewContent);
            $this->assertStringContainsString('22.874.000', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_uses_team_column_for_monthly_chart_not_kro_column(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithPeriodAndTeamColumns();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-periode-tim.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview', ['chart_month' => 'Februari']));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Februari', $overviewContent);
            $this->assertStringContainsString('PSS', $overviewContent);
            $this->assertStringContainsString('Neraca', $overviewContent);
            $this->assertStringContainsString('Distribusi', $overviewContent);
            $this->assertStringContainsString('Produksi', $overviewContent);
            $this->assertStringContainsString('Sosial', $overviewContent);
            $this->assertStringContainsString('49.074.000', $overviewContent);
            $this->assertStringContainsString('30.071.650', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_filters_out_kro_code_labels_from_chart(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithKroCodesInKroColumn();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-kro-code.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview', ['chart_month' => 'Februari']));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Februari', $overviewContent);
            $this->assertStringContainsString('Produksi', $overviewContent);
            $this->assertStringNotContainsString('2906.BMA.005', $overviewContent);
            $this->assertStringNotContainsString('2910.BMA.007', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_filters_out_long_descriptive_labels_that_are_not_teams(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithDescriptiveLabels();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-descriptive.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview', ['chart_month' => 'Februari']));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Februari', $overviewContent);
            $this->assertStringContainsString('PSS', $overviewContent);
            $this->assertStringNotContainsString('honor petugas pendataan lapangan survei industri besar dan sedang', $overviewContent);
            $this->assertStringNotContainsString('triwulanan', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_requires_month_selection_before_showing_chart(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithPeriodAndTeamColumns();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-periode-tim.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview'));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Pilih bulan terlebih dahulu', $overviewContent);
            $this->assertStringNotContainsString('PSS | Target', $overviewContent);

            $filteredResponse = $this->get(route('overview', ['chart_month' => 'Februari']));
            $this->assertSame(200, $filteredResponse->getStatusCode());
            $filteredContent = $filteredResponse->getContent();
            $this->assertStringContainsString('Februari', $filteredContent);
            $this->assertStringContainsString('PSS', $filteredContent);
            $this->assertStringContainsString('Neraca', $filteredContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_detects_march_from_sheet_title_with_teams_in_first_column(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileForMarchTitleLayout();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-maret.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview'));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Data bulanan belum ditemukan', $overviewContent);

            $maretResponse = $this->get(route('overview', ['chart_month' => 'Maret']));
            $this->assertSame(200, $maretResponse->getStatusCode());

            $maretContent = $maretResponse->getContent();
            $this->assertStringContainsString('Maret', $maretContent);
            $this->assertStringContainsString('IPDS', $maretContent);
            $this->assertStringContainsString('PSS', $maretContent);
            $this->assertStringContainsString('Neraca', $maretContent);
            $this->assertStringContainsString('Target vs Realisasi', $maretContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    public function test_it_reads_belanja_barang_when_target_and_realisasi_columns_shift_right(): void
    {
        $this->clearUploadedFiles();

        $tempFile = $this->createSpreadsheetFileWithShiftedBelanjaBarangColumns();

        try {
            $response = $this->post(route('keutata.import'), [
                'excel_file' => new UploadedFile(
                    $tempFile,
                    'monitoring-shifted-belanja.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ]);

            $this->assertSame(302, $response->getStatusCode(), 'Upload should redirect');

            $overviewResponse = $this->get(route('overview', ['chart_month' => 'April']));
            $this->assertSame(200, $overviewResponse->getStatusCode());

            $overviewContent = $overviewResponse->getContent();
            $this->assertStringContainsString('Belanja Barang', $overviewContent);
            $this->assertStringContainsString('April', $overviewContent);
            $this->assertStringContainsString('PSS', $overviewContent);
            $this->assertStringContainsString('621.000', $overviewContent);
            $this->assertStringContainsString('59.727.000', $overviewContent);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            $this->clearUploadedFiles();
        }
    }

    private function createSpreadsheetFile(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap');

        $sheet->setCellValue('A2', 'No');
        $sheet->setCellValue('B2', 'Uraian KRO');
        $sheet->setCellValue('C2', 'Nominal RPD');
        $sheet->setCellValue('D2', 'Deviasi 5%');
        $sheet->setCellValue('E2', 'Uraian');
        $sheet->setCellValue('F2', 'Nominal Pengajuan');

        $sheet->setCellValue('A3', '1');
        $sheet->setCellValue('B3', 'Data A');
        $sheet->setCellValue('C3', '1000');
        $sheet->setCellValue('D3', '50');
        $sheet->setCellValue('E3', 'Uraian A');
        $sheet->setCellValue('F3', '1050');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileWithSynonymHeaders(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Februari');

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Uraian KRO');
        $sheet->setCellValue('C1', 'RPD');
        $sheet->setCellValue('D1', 'Fa detail');

        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'PSS');
        $sheet->setCellValue('C2', '300.000');
        $sheet->setCellValue('D2', '0');

        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Distribusi');
        $sheet->setCellValue('C2', '22.874.000');
        $sheet->setCellValue('D2', '17.008.000');

        $marchSheet = $spreadsheet->createSheet();
        $marchSheet->setTitle('Maret');
        $marchSheet->setCellValue('A1', 'No');
        $marchSheet->setCellValue('B1', 'Uraian KRO');
        $marchSheet->setCellValue('C1', 'Target');
        $marchSheet->setCellValue('D1', 'Realisasi');
        $marchSheet->setCellValue('A2', '1');
        $marchSheet->setCellValue('B2', 'Produksi');
        $marchSheet->setCellValue('C2', '26.187.000');
        $marchSheet->setCellValue('D2', '21.824.000');

        $aprilSheet = $spreadsheet->createSheet();
        $aprilSheet->setTitle('April');
        $aprilSheet->setCellValue('A1', 'No');
        $aprilSheet->setCellValue('B1', 'Uraian KRO');
        $aprilSheet->setCellValue('C1', 'Pagu');
        $aprilSheet->setCellValue('D1', 'Actual');
        $aprilSheet->setCellValue('A2', '1');
        $aprilSheet->setCellValue('B2', 'Sosial');
        $aprilSheet->setCellValue('C2', '201.150.000');
        $aprilSheet->setCellValue('D2', '178.328.000');

        $sheet->setCellValue('C3', '22.874.000');
        $sheet->setCellValue('D3', '17.008.000');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_syn_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileWithPeriodAndTeamColumns(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap 2026');

        $sheet->setCellValue('A1', 'Februari 2026');
        $sheet->setCellValue('B1', 'Tim');
        $sheet->setCellValue('C1', 'RPD');
        $sheet->setCellValue('D1', 'Fa detail');

        $sheet->setCellValue('A2', 'Februari 2026');
        $sheet->setCellValue('B2', 'PSS');
        $sheet->setCellValue('C2', '300,000');
        $sheet->setCellValue('D2', '0');

        $sheet->setCellValue('A3', 'Februari 2026');
        $sheet->setCellValue('B3', 'Neraca');
        $sheet->setCellValue('C3', '900,000');
        $sheet->setCellValue('D3', '900,000');

        $sheet->setCellValue('A4', 'Februari 2026');
        $sheet->setCellValue('B4', 'Distribusi');
        $sheet->setCellValue('C4', '13,805,000');
        $sheet->setCellValue('D4', '14,635,000');

        $sheet->setCellValue('A5', 'Februari 2026');
        $sheet->setCellValue('B5', 'Produksi');
        $sheet->setCellValue('C5', '53,990,000');
        $sheet->setCellValue('D5', '37,239,000');

        $sheet->setCellValue('A6', 'Februari 2026');
        $sheet->setCellValue('B6', 'Sosial');
        $sheet->setCellValue('C6', '49,074,000');
        $sheet->setCellValue('D6', '30,071,650');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_period_team_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileWithKroCodesInKroColumn(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap 2026');

        $sheet->setCellValue('A1', 'Februari 2026');
        $sheet->setCellValue('B1', 'Uraian KRO');
        $sheet->setCellValue('C1', 'Uraian');
        $sheet->setCellValue('D1', 'RPD');
        $sheet->setCellValue('E1', 'Fa detail');

        $sheet->setCellValue('A2', 'Februari 2026');
        $sheet->setCellValue('B2', '2906.BMA.005');
        $sheet->setCellValue('C2', 'Produksi');
        $sheet->setCellValue('D2', '53,990,000');
        $sheet->setCellValue('E2', '37,239,000');

        $sheet->setCellValue('A3', 'Februari 2026');
        $sheet->setCellValue('B3', '2910.BMA.007');
        $sheet->setCellValue('C3', 'Sosial');
        $sheet->setCellValue('D3', '49,074,000');
        $sheet->setCellValue('E3', '30,071,650');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_kro_code_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileWithDescriptiveLabels(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap 2026');

        $sheet->setCellValue('A1', 'Februari 2026');
        $sheet->setCellValue('B1', 'Tim');
        $sheet->setCellValue('C1', 'RPD');
        $sheet->setCellValue('D1', 'Fa detail');

        $sheet->setCellValue('A2', 'Februari 2026');
        $sheet->setCellValue('B2', 'honor petugas pendataan lapangan survei industri besar dan sedang (ibs) triwulanan');
        $sheet->setCellValue('C2', '53,990,000');
        $sheet->setCellValue('D2', '37,239,000');

        $sheet->setCellValue('A3', 'Februari 2026');
        $sheet->setCellValue('B3', 'PSS');
        $sheet->setCellValue('C3', '300,000');
        $sheet->setCellValue('D3', '0');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_desc_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileForMarchTitleLayout(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RPD Bulan Maret 2026');

        $sheet->setCellValue('A1', 'RPD Bulan Maret 2026');
        $sheet->setCellValue('B1', 'Target');
        $sheet->setCellValue('C1', 'Realisasi');

        $sheet->setCellValue('A2', 'IPDS');
        $sheet->setCellValue('B2', '150,000');
        $sheet->setCellValue('C2', '');

        $sheet->setCellValue('A3', 'PSS');
        $sheet->setCellValue('B3', '300,000');
        $sheet->setCellValue('C3', '');

        $sheet->setCellValue('A4', 'Neraca');
        $sheet->setCellValue('B4', '3,364,000');
        $sheet->setCellValue('C4', '2,913,000');

        $sheet->setCellValue('A5', 'Distribusi');
        $sheet->setCellValue('B5', '22,874,000');
        $sheet->setCellValue('C5', '17,008,000');

        $sheet->setCellValue('A6', 'Produksi');
        $sheet->setCellValue('B6', '26,187,000');
        $sheet->setCellValue('C6', '21,824,000');

        $sheet->setCellValue('A7', 'Sosial');
        $sheet->setCellValue('B7', '201,150,000');
        $sheet->setCellValue('C7', '178,328,000');

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_maret_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function createSpreadsheetFileWithShiftedBelanjaBarangColumns(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap April');

        $sheet->setCellValue('A1', 'RPD Bulan April 2026 Belanja Barang');
        $sheet->setCellValue('C2', 'Target');
        $sheet->setCellValue('D2', 'Realisasi');

        $sheet->setCellValue('B3', 'IPDS');
        $sheet->setCellValue('C3', 300000);
        $sheet->setCellValue('D3', 300000);

        $sheet->setCellValue('B4', 'PSS');
        $sheet->setCellValue('C4', 621000);
        $sheet->setCellValue('D4', 0);

        $sheet->setCellValue('B5', 'Neraca');
        $sheet->setCellValue('C5', 4371000);
        $sheet->setCellValue('D5', 1060000);

        $sheet->setCellValue('B6', 'Distribusi');
        $sheet->setCellValue('C6', 27070000);
        $sheet->setCellValue('D6', 3996000);

        $sheet->setCellValue('B7', 'Produksi');
        $sheet->setCellValue('C7', 59727000);
        $sheet->setCellValue('D7', 20600000);

        $sheet->setCellValue('B8', 'Sosial');
        $sheet->setCellValue('C8', 26633000);
        $sheet->setCellValue('D8', 38150000);

        $sheet->setCellValue('A10', 'RPD Bulan April 2026 Belanja Pegawai');
        $sheet->setCellValue('C11', 'Target');
        $sheet->setCellValue('D11', 'Realisasi');
        $sheet->setCellValue('B12', 'Umum');
        $sheet->setCellValue('C12', 500000);
        $sheet->setCellValue('D12', 450000);

        $tempFile = tempnam(sys_get_temp_dir(), 'keutata_shifted_belanja_');

        if ($tempFile === false) {
            $this->fail('Unable to create a temporary spreadsheet file.');
        }

        $xlsxFile = $tempFile.'.xlsx';
        rename($tempFile, $xlsxFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxFile);

        return $xlsxFile;
    }

    private function clearUploadedFiles(): void
    {
        foreach (glob(storage_path('app/private/uploads/*')) ?: [] as $existingFile) {
            @unlink($existingFile);
        }
    }
}
