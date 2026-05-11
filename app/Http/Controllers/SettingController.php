<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $googleSheetsId = Setting::where('key', 'google_sheets_spreadsheet_id')->first()?->value 
            ?? config('services.google_sheets.spreadsheet_id');
        
        return view('settings.index', [
            'googleSheetsId' => $googleSheetsId,
        ]);
    }

    public function updateGoogleSheets(Request $request): RedirectResponse
    {
        $request->validate([
            'spreadsheet_id' => 'required|string|min:20',
        ], [
            'spreadsheet_id.required' => 'ID Spreadsheet harus diisi.',
            'spreadsheet_id.min' => 'ID Spreadsheet terlalu pendek.',
        ]);

        $setting = Setting::firstOrCreate(
            ['key' => 'google_sheets_spreadsheet_id'],
            ['value' => $request->input('spreadsheet_id')]
        );

        $setting->update(['value' => $request->input('spreadsheet_id')]);

        return redirect()->route('admin.settings.index')->with('success', 'Konfigurasi Google Sheets berhasil diperbarui.');
    }
}
