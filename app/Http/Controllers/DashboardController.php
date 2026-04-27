<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DashboardController extends Controller
{
    public function overview(): View
    {
        return view('overview');
    }

    public function keutata(): View
    {
        $rows = [];
        $error = null;
        $fileName = null;

        try {
            $files = glob(storage_path('app/uploads/*.xlsx')) ?: [];

            if (! empty($files)) {
                $filePath = $files[0];
                $fileName = basename($filePath);

                $spreadsheet = IOFactory::load($filePath);

                foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                    $sheetName = $sheet->getTitle();
                    $highestRow = $sheet->getHighestDataRow();

                    for ($row = 5; $row <= $highestRow; $row++) {
                        $no = trim((string) $sheet->getCell("A{$row}")->getFormattedValue());
                        $uraianKro = trim((string) $sheet->getCell("B{$row}")->getFormattedValue());
                        $nominalRpd = trim((string) $sheet->getCell("C{$row}")->getFormattedValue());
                        $deviasi = trim((string) $sheet->getCell("D{$row}")->getFormattedValue());
                        $uraian = trim((string) $sheet->getCell("E{$row}")->getFormattedValue());
                        $nominalPengajuan = trim((string) $sheet->getCell("F{$row}")->getFormattedValue());

                        if (
                            $no === '' &&
                            $uraianKro === '' &&
                            $nominalRpd === '' &&
                            $deviasi === '' &&
                            $uraian === '' &&
                            $nominalPengajuan === ''
                        ) {
                            continue;
                        }

                        $rows[] = [
                            'sheet' => $sheetName,
                            'no' => $no,
                            'uraian_kro' => $uraianKro,
                            'nominal_rpd' => $nominalRpd,
                            'deviasi' => $deviasi,
                            'uraian' => $uraian,
                            'nominal_pengajuan' => $nominalPengajuan,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            $error = 'Gagal membaca file Excel: '.$e->getMessage();
        }

        return view('keutata', [
            'rows' => $rows,
            'fileName' => $fileName,
            'error' => $error,
        ]);
    }
}
