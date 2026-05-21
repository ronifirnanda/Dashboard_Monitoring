@extends('layouts.app')

@section('title', 'Keutata')

@section('subtitle', 'Upload file Excel baru, sistem akan mempelajari struktur sheet terlebih dahulu, lalu menampilkan data yang berhasil dikenali.')

@section('page-actions')
<a href="#" class="btn-pill btn-outline-soft"><i class="bi bi-funnel me-2"></i>Filter</a>
@endsection

@section('content')
<div class="panel table-card">
    <div class="panel-header" style="display: block;">
        <div>
            <div class="panel-title">Daftar Keutata Monitoring</div>
            <div class="panel-small">Data dari file Excel terbaru.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center mt-3">
            @if(!empty($fileName))
                <span id="keutataFileNameBadge" class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ $fileName }}</span>
            @endif
            <span id="keutataRowsBadge" class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-list-check me-2"></i>{{ count($rows) }} Rows</span>
        </div>

        <div style="margin-top: 1rem; padding: 0.9rem; border: 1px solid #dfeee1; border-radius: 14px; background: #fbfefb;">
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span style="font-size: 0.85rem; color: #5e6f60; font-weight: 700;">Filter data:</span>

                @if(!empty($monthOptions))
                    <form method="GET" action="{{ route('keutata') }}" class="d-flex align-items-center gap-2 flex-wrap">
                        @if(!empty($selectedUploadId))
                            <input type="hidden" name="upload" value="{{ $selectedUploadId }}">
                        @endif

                        @if(!empty($selectedSheet))
                            <input type="hidden" name="sheet" value="{{ $selectedSheet }}">
                        @endif

                        <label for="monthFilter" style="font-size: 0.85rem; color: #5e6f60; font-weight: 600;">Bulan:</label>
                        <select id="monthFilter" name="month" onchange="this.form.submit()" class="form-select form-select-sm" style="min-width: 170px; border-radius: 999px;">
                            <option value="">Semua bulan</option>
                            @foreach($monthOptions as $monthOption)
                                <option value="{{ $monthOption }}" {{ ($selectedMonth ?? '') === $monthOption ? 'selected' : '' }}>
                                    {{ $monthOption }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if(!empty($sheetOptions))
                <div class="d-flex gap-2 flex-wrap align-items-center mt-3">
                    <span style="font-size: 0.85rem; color: #5e6f60; font-weight: 700;">Sheet:</span>

                    @foreach($sheetOptions as $sheetOption)
                        @php
                            $sheetQuery = ['sheet' => $sheetOption];

                            if (!empty($selectedUploadId)) {
                                $sheetQuery['upload'] = $selectedUploadId;
                            }

                            if (!empty($selectedMonth)) {
                                $sheetQuery['month'] = $selectedMonth;
                            }
                        @endphp

                        <a href="{{ route('keutata', $sheetQuery) }}"
                           class="btn-pill py-1 px-3"
                           style="font-size: 0.85rem; border: 1px solid {{ ($selectedSheet ?? '') === $sheetOption ? '#1f6b45' : '#d7e2d9' }}; background: {{ ($selectedSheet ?? '') === $sheetOption ? '#1f6b45' : '#ffffff' }}; color: {{ ($selectedSheet ?? '') === $sheetOption ? '#ffffff' : '#304935' }}; text-decoration: none;">
                            {{ $sheetOption }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4">
            <strong>Berhasil:</strong> {{ session('success') }}
        </div>
    @endif

    @if(session('uploadError'))
        <div class="alert alert-danger border-0 rounded-4">
            <strong>Upload gagal:</strong> {{ session('uploadError') }}
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger border-0 rounded-4">
            <strong>Gagal:</strong> {{ $error }}
        </div>
    @endif

    @if(!empty($isAdminMode) && !empty($selectedUploadId))
        <div class="alert alert-info border-0 rounded-4">
            <div>
                <strong>Mode edit aktif.</strong>
                <div style="margin-top:6px; color: #28503a;">Ubah sel langsung lalu klik simpan agar perubahan masuk ke file Excel dan Google Sheets bila terhubung.</div>
            </div>
            <div class="mt-3">
                <span class="btn-pill btn-outline-soft py-1 px-3 me-2" id="keutataSaveStatus">Belum ada perubahan</span>
                <button type="button" class="btn btn-sm btn-primary" id="keutataSaveButton">Simpan perubahan</button>
            </div>
        </div>
    @endif

    @if(empty($rawSheets) && empty($rows))
        <div class="reminder-card">
            <div>
                <h4>Belum ada data</h4>
                <p>Silahkan import file Excel terlebih dahulu agar tabel terisi.</p>
            </div>
        </div>
    @elseif(empty($selectedSheetData) && !empty($sheetOptions))
        <div class="reminder-card">
            <div>
                <h4>Pilih sheet terlebih dahulu</h4>
                <p>Semua sheet sudah tersedia di atas. Pilih satu sheet untuk menampilkan datanya.</p>
            </div>
        </div>
    @elseif(empty($rawSheets))
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Sheet</th>
                        <th>Tim</th>
                        <th>Nominal RPD</th>
                        <th>Deviasi 5%</th>
                        <th>Uraian</th>
                        <th>Nominal Pengajuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['no'] }}</td>
                            <td><span class="badge text-bg-light border">{{ $row['sheet'] }}</span></td>
                            <td>{{ $row['tim_display'] ?? '-' }}</td>
                            <td>{{ $row['nominal_rpd_display'] ?? $row['nominal_rpd'] }}</td>
                            <td>{{ $row['deviasi'] }}</td>
                            <td>{{ $row['uraian'] }}</td>
                            <td>{{ $row['nominal_pengajuan_display'] ?? $row['nominal_pengajuan'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif(!empty($selectedSheetData))
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <h5 class="mb-0" style="color: #304935; font-weight: 600;">Sheet: {{ $selectedSheetData['sheet'] }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="btn-pill btn-outline-soft py-1 px-3" style="background: #e7f3ff; border-color: #c7e0fb; color: #205d93;">
                        <i class="bi bi-calendar3 me-1"></i>{{ $selectedSheetData['month_label'] ?? 'Tanpa Bulan' }}
                    </span>
                    <span class="btn-pill btn-outline-soft py-1 px-3">
                        {{ count($selectedSheetData['columns']) }} kolom | {{ count($selectedSheetData['rows']) }} baris
                    </span>
                </div>
            </div>

            <div class="table-responsive" style="border: 1px solid #dfeee1; border-radius: 10px; max-height: 72vh; overflow: auto;">
                <table class="table table-sm align-middle mb-0" style="font-size: 0.9rem; min-width: 100%;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="min-width: 80px; background: #f5faf5; position: sticky; left: 0; z-index: 3;">Row</th>
                            @foreach($selectedSheetData['columns'] as $columnLetter)
                                <th style="min-width: 150px; background: #f5faf5;">{{ $columnLetter }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedSheetData['rows'] as $rawRow)
                            <tr>
                                <td style="font-weight: 600; color: #5e6f60; background: #fff; position: sticky; left: 0; z-index: 1;">{{ $rawRow['row_number'] }}</td>
                                @foreach($rawRow['cells'] as $cellIndex => $cell)
                                    @php
                                        $columnLetter = $selectedSheetData['columns'][$cellIndex] ?? null;
                                        $cellAddress = $columnLetter ? $columnLetter.$rawRow['row_number'] : null;
                                        $isEditableCell = !empty($isAdminMode) && !empty($selectedUploadId) && $columnLetter !== null;
                                    @endphp
                                    <td
                                        style="white-space: nowrap; min-width: 150px; {{ $isEditableCell ? 'background: #fffdf4; cursor: text;' : '' }}"
                                        @if($isEditableCell)
                                            contenteditable="true"
                                            spellcheck="false"
                                            data-editable-cell="true"
                                            data-cell-address="{{ $cellAddress }}"
                                            data-sheet-name="{{ $selectedSheetData['sheet'] }}"
                                            data-upload-id="{{ $selectedUploadId }}"
                                            data-original-value="{{ e($cell) }}"
                                        @endif
                                    >{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@push('scripts')
@if(!empty($isAdminMode) && !empty($selectedUploadId) && !empty($selectedSheetData))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cells = Array.from(document.querySelectorAll('[data-editable-cell="true"]'));
    const saveButton = document.getElementById('keutataSaveButton');
    const statusBadge = document.getElementById('keutataSaveStatus');
    const saveUrl = @json(route('keutata.update-cell'));
    const fragmentBase = @json(route('keutata.fragment'));
    const selectedUploadId = @json($selectedUploadId ?? null);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!cells.length || !saveButton || !statusBadge || !csrfToken) {
        return;
    }

    const variants = {
        idle: { border: '#d7e2d9', background: '#ffffff', color: '#304935' },
        dirty: { border: '#f0d48a', background: '#fff7df', color: '#8b6512' },
        saving: { border: '#c7e0fb', background: '#e7f3ff', color: '#205d93' },
        success: { border: '#cdeccf', background: '#edf9ef', color: '#2f6b3f' },
        error: { border: '#f2c6c6', background: '#fff0f0', color: '#a33a3a' },
    };

    const dirtyCells = new Set();

    const setStatus = (text, variant) => {
        statusBadge.textContent = text;
        statusBadge.style.borderColor = variant.border;
        statusBadge.style.background = variant.background;
        statusBadge.style.color = variant.color;
    };

    const normalizeValue = (cell) => (cell.textContent || '').replace(/\u00a0/g, ' ').trimEnd();

    const setDirtyState = (cell, isDirty) => {
        cell.dataset.dirty = isDirty ? '1' : '0';
        cell.style.background = isDirty ? '#fff7df' : '#fffdf4';

        if (isDirty) {
            dirtyCells.add(cell);
        } else {
            dirtyCells.delete(cell);
        }

        saveButton.disabled = dirtyCells.size === 0;
        statusBadge.textContent = dirtyCells.size > 0 ? `${dirtyCells.size} perubahan belum disimpan` : 'Belum ada perubahan';
        statusBadge.style.borderColor = dirtyCells.size > 0 ? variants.dirty.border : variants.idle.border;
        statusBadge.style.background = dirtyCells.size > 0 ? variants.dirty.background : variants.idle.background;
        statusBadge.style.color = dirtyCells.size > 0 ? variants.dirty.color : variants.idle.color;
    };

    const refreshSummary = async () => {
        const fragmentUrl = selectedUploadId ? `${fragmentBase}?upload=${encodeURIComponent(selectedUploadId)}` : fragmentBase;

        try {
            const response = await fetch(fragmentUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.fileName !== undefined) {
                const fileBadge = document.getElementById('keutataFileNameBadge');
                if (fileBadge) fileBadge.textContent = data.fileName;
            }

            if (data.rows_count !== undefined) {
                const rowsBadge = document.getElementById('keutataRowsBadge');
                if (rowsBadge) rowsBadge.textContent = (data.rows_count || 0) + ' Rows';
            }
        } catch (error) {
            // Ignore summary refresh errors so saving still completes.
        }
    };

    const saveCell = async (cell) => {
        const originalValue = cell.dataset.originalValue ?? '';
        const currentValue = normalizeValue(cell);

        if (currentValue === originalValue) {
            setDirtyState(cell, false);
            return true;
        }

        cell.style.opacity = '0.7';

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    upload_id: cell.dataset.uploadId,
                    sheet: cell.dataset.sheetName,
                    cell: cell.dataset.cellAddress,
                    value: currentValue,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Gagal menyimpan perubahan.');
            }

            cell.textContent = payload.value ?? '';
            cell.dataset.originalValue = payload.value ?? '';
            setDirtyState(cell, false);

            if (payload.google_sync_error) {
                setStatus('Tersimpan, sinkron Google gagal', variants.error);
            } else {
                setStatus(
                    payload.synced_to_google_sheet ? 'Tersimpan dan Google Sheets diperbarui' : 'Tersimpan',
                    variants.success
                );
            }

            await refreshSummary();
            return true;
        } catch (error) {
            setStatus(error.message || 'Gagal menyimpan.', variants.error);
            return false;
        } finally {
            cell.style.opacity = '';
        }
    };

    const markCellDirty = (cell) => {
        const currentValue = normalizeValue(cell);
        const originalValue = cell.dataset.originalValue ?? '';

        setDirtyState(cell, currentValue !== originalValue);
    };

    cells.forEach((cell) => {
        cell.addEventListener('input', () => {
            markCellDirty(cell);
        });

        cell.addEventListener('paste', (event) => {
            event.preventDefault();
            const text = event.clipboardData?.getData('text/plain') ?? '';
            document.execCommand('insertText', false, text);
            window.setTimeout(() => markCellDirty(cell), 0);
        });

        cell.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                cell.blur();
            }
        });
    });

    saveButton.addEventListener('click', async () => {
        if (dirtyCells.size === 0) {
            setStatus('Tidak ada perubahan untuk disimpan', variants.idle);
            return;
        }

        saveButton.disabled = true;
        setStatus('Menyimpan perubahan...', variants.saving);

        const pendingCells = Array.from(dirtyCells);
        let savedCount = 0;
        let failedCount = 0;

        for (const cell of pendingCells) {
            const saved = await saveCell(cell);
            if (saved) {
                savedCount += 1;
            } else {
                failedCount += 1;
            }
        }

        saveButton.disabled = dirtyCells.size === 0;

        if (failedCount > 0) {
            setStatus(`${savedCount} sel tersimpan, ${failedCount} gagal`, variants.error);
        } else {
            setStatus('Semua perubahan tersimpan', variants.success);
        }

        window.setTimeout(() => {
            if (dirtyCells.size === 0) {
                setStatus('Belum ada perubahan', variants.idle);
            }
        }, 1400);
    });
});
</script>
@endif
@endpush
@endsection
