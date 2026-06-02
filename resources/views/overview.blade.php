@extends('layouts.app')

@section('title', 'Overview')

@section('page-actions')
<a href="#charts" class="btn-pill btn-primary-soft"><i class="bi bi-bar-chart-line me-2"></i>Lihat</a>
@if(auth()->check() && auth()->user()->role === 'admin')
<a href="#data-source" class="btn-pill btn-outline-soft"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Data</a>
@endif
@endsection

@section('content')
<style>
    :root {
        --chart-forest: #1f6b45;
        --chart-leaf: #4d9f60;
        --chart-soft: #edf5ef;
        --chart-border: #dbe8dd;
        --chart-surface: #ffffff;
        --chart-shadow: 0 18px 40px rgba(25, 56, 33, 0.08);
    }

    .dashboard-charts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .chart-card {
        min-height: 340px;
        border: 1px solid var(--chart-border);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbf8 100%);
        box-shadow: var(--chart-shadow);
        overflow: hidden;
    }

    .metric-label {
        font-size: 0.76rem;
        color: #5e6f60;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .metric-value {
        font-weight: 800;
        color: #203d2a;
        font-size: 1.08rem;
    }

    .chart-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.38);
    }

    .chart-pill.target {
        background: linear-gradient(180deg, #eff8f1 0%, #e1f0e5 100%);
        color: var(--chart-forest);
    }

        background: linear-gradient(180deg, #f4f8f5 0%, #e9f1eb 100%);
        color: #35513d;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);
    }

    .chart-empty-note {
    }

    .bar-tone-realisasi {
        background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%);
    }

    .chart-note {
        margin-top: 0.6rem;
        font-size: 0.8rem;
        color: #637266;
    }

    .upload-mode-shell {
        margin: 1rem 1.25rem 0;
        max-width: 920px;
        padding: 0; /* no wrapper padding */
        border: none; /* remove border */
        border-radius: 0;
        background: transparent; /* remove background */
        box-shadow: none; /* remove shadow */
    }

    .upload-choices {
        display: flex;
        gap: 0.6rem;
        margin-top: 0.4rem;
    }

    .upload-choice {
        padding: 0.6rem 0.9rem;
        border-radius: 12px;
        border: 1px solid #d6e6de;
        background: #ffffff;
        color: #274938;
        font-weight: 700;
        cursor: pointer;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
    }

    .upload-choice.active {
        background: linear-gradient(90deg, #e6f6ea 0%, #dff3e6 100%);
        border-color: #9fd3a8;
        box-shadow: 0 6px 14px rgba(47,138,74,0.06);
    }

    .upload-mode-label {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #58705c;
        margin-bottom: 0.75rem;
    }

    .upload-mode-label i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.7rem;
        height: 1.7rem;
        border-radius: 999px;
        background: rgba(31, 107, 69, 0.12);
        color: var(--chart-forest);
    }

    .upload-mode-select {
        min-height: 3rem;
        border-radius: 14px;
        border-color: #cfdccf;
        background-color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .upload-mode-row {
        margin: 1rem 1.25rem 0;
    }

    .upload-mode-row .upload-mode-buttons {
        display: flex;
        gap: 0.6rem;
        margin-top: 0.4rem;
        flex-wrap: wrap;
    }

    .upload-auto-card {
        margin: 1rem 1.25rem 0;
        padding: 1rem 1.15rem;
        border: 1px solid #dce8de;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfefb 100%);
        box-shadow: 0 8px 20px rgba(25, 56, 33, 0.05);
    }

    .upload-auto-card .upload-auto-row {
        padding: 0;
        margin-top: 0.85rem;
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .upload-manual-card {
        margin: 1rem 1.25rem 0;
        padding: 1rem 1.15rem;
        border: 1px solid #dce8de;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfefb 100%);
        box-shadow: 0 8px 20px rgba(25, 56, 33, 0.05);
    }

    .upload-manual-card .upload-manual-row {
        margin-top: 0.85rem;
        padding: 0;
    }

    /* Chart Bar Hover Effects */
    .chart-bar-target {
        transition: all 0.2s ease;
    }

    .chart-bar-target:hover {
        background: linear-gradient(180deg, #dc3545 0%, #c82333 100%) !important;
        filter: brightness(1.05);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.25) !important;
    }

    .chart-bar-realisasi {
        transition: all 0.2s ease;
    }

    .chart-bar-realisasi:hover {
        background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%) !important;
        filter: brightness(1.05);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.25) !important;
    }
</style>

@if(auth()->check() && auth()->user()->role === 'admin')
    @php
        $selectedUploadMode = request('upload_mode', 'manual');
    @endphp

    <div id="data-source" class="panel mb-4">
        <div class="panel-header">
            <div>
                <div class="panel-title">Upload Data Baru</div>
                <div class="panel-small">Pilih mode input data: manual untuk upload file Excel, otomatis untuk sync dari Google Sheets.</div>
            </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bars = document.querySelectorAll('div[style*="background: linear-gradient"]');
            bars.forEach(bar => {
                const s = bar.getAttribute('style');
                if (s.includes('#1f6b45') && s.includes('#2f8b59')) bar.classList.add('chart-bar-target');
                else if (s.includes('#7bbd86') && s.includes('#4d9f60')) bar.classList.add('chart-bar-realisasi');
            });
        });
        </script>
        </div>

        <div class="upload-mode-row">
            <div class="upload-mode-label"><i class="bi bi-sliders2"></i><span>Mode Upload</span></div>
            <div class="upload-mode-buttons">
                <a href="{{ route('overview', array_merge(request()->except('upload_mode'), ['upload_mode' => 'manual'])) }}" class="btn-pill @if($selectedUploadMode === 'manual') btn-primary-soft @else btn-outline-soft @endif py-2 px-3"><i class="bi bi-upload me-2"></i>Manual - Upload Excel</a>
                <a href="{{ route('overview', array_merge(request()->except('upload_mode'), ['upload_mode' => 'auto'])) }}" class="btn-pill @if($selectedUploadMode === 'auto') btn-primary-soft @else btn-outline-soft @endif py-2 px-3"><i class="bi bi-cloud-arrow-down me-2"></i>Otomatis - Sync Google Sheets</a>
            </div>
        </div>

        @if($selectedUploadMode === 'manual')
            <form id="upload" action="{{ route('keutata.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @if(session('success'))
                    <div class="alert alert-success border-0 rounded-4" style="margin: 1rem;">
                        <strong>Berhasil:</strong> {{ session('success') }}
                    </div>
                @endif

                @if(session('uploadError'))
                    <div class="alert alert-danger border-0 rounded-4" style="margin: 1rem;">
                        <strong>Upload gagal:</strong> {{ session('uploadError') }}
                    </div>
                @endif

                <div class="upload-manual-card">
                    <div class="panel-header" style="padding-top: 0;">
                        <div>
                            <div class="panel-title">Manual</div>
                            <div class="panel-small">Gunakan ini jika ingin mengunggah file Excel dari komputer.</div>
                        </div>
                    </div>

                    <div class="upload-manual-row">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-end gap-3">
                        <div class="grow">
                            <label for="excel_file" class="form-label fw-semibold mb-2">Pilih File Excel</label>
                            <input
                                type="file"
                                name="excel_file"
                                id="excel_file"
                                class="form-control rounded-4 @error('excel_file') is-invalid @enderror"
                                accept=".xlsx,.xls,.xlsm,.csv"
                                required
                            >
                            @error('excel_file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <button type="submit" class="btn-pill btn-primary-soft border-0">
                                <i class="bi bi-upload me-2"></i>Upload & Pelajari
                            </button>
                        </div>
                    </div>
                    <small class="text-muted mt-3 d-block">File Excel akan dianalisis, header terdeteksi otomatis, lalu dipakai oleh dashboard.</small>
                </div>
                </div>
            </form>
        @else
            <div class="upload-auto-card">
                <div class="panel-header" style="padding-top: 0;">
                    <div>
                        <div class="panel-title">Otomatis</div>
                        <div class="panel-small">Gunakan ini jika ingin menarik data langsung dari Google Sheets.</div>
                    </div>
                </div>

                <div class="upload-auto-row">
                    <form action="{{ route('keutata.sync-google-sheet') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn-pill btn-primary-soft border-0">
                            <i class="bi bi-cloud-arrow-down me-2"></i>Sync dari Google Sheets
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endif

<section id="charts" class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Dashboard</div>
            <div class="panel-small">1 menampilkan Belanja Barang, 2 menampilkan Belanja Pegawai.</div>
        </div>
    </div>

    <div style="padding: 0 1.25rem 1rem 1.25rem;">
        <form method="GET" action="{{ route('overview') }}" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:end;">
            @if(request('upload'))
                <input type="hidden" name="upload" value="{{ request('upload') }}">
            @endif
            <div style="min-width:220px; flex:1; max-width:360px;">
                <label for="chart_month" class="form-label fw-semibold mb-2">Filter Bulan</label>
                <select name="chart_month" id="chart_month" class="form-select rounded-4" @if(empty($monthOptions)) disabled @endif onchange="this.form.submit()">
                    <option value="">-- pilih bulan --</option>
                    @foreach ($monthOptions as $monthOption)
                        <option value="{{ $monthOption }}" @selected(($selectedMonth ?? '') === $monthOption)>{{ $monthOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn-pill btn-primary-soft border-0" @if(empty($monthOptions)) disabled @endif>Tampilkan</button>
            </div>
        </form>

        @if(empty($monthOptions))
            <div style="width:100%; font-size:.82rem; color:#7a8a7d;">Belum ada data bulan yang bisa dipilih.</div>
        @endif
    </div>

    <div class="dashboard-charts">
        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Belanja Barang</div>
                    <div class="panel-small">Target vs Realisasi untuk Belanja Barang.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalBelanjaBarang" aria-label="Perjelas grafik Belanja Barang" title="Perjelas grafik">
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>

            @if (!empty($monthOptions) || !empty($selectedMonthDataBarang))
                <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-top:.75rem; margin-bottom:1rem;">
                    <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Target</div>
                        <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaBarang['total_target_formatted'] }}</div>
                    </div>
                    <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Realisasi</div>
                        <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaBarang['total_realisasi_formatted'] }}</div>
                    </div>
                    <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Serapan Rata-rata</div>
                        <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartBelanjaBarang['overall_ratio'], 2, ',', '.') }}%</div>
                    </div>
                </div>

                @if (!empty($selectedMonthDataBarang))
                    <div style="margin-top:.75rem; padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.75rem; flex-wrap:wrap;">
                            <div>
                                <h5 style="margin:0; color:#304935; font-size:1.05rem; font-weight:700;">{{ $selectedMonthDataBarang['label'] }}</h5>
                                <div style="font-size:.78rem; color:#7a8a7d;">Pilih bulan untuk menampilkan bulan tersebut.</div>
                            </div>
                            <div style="font-size:.8rem; color:#6b7c72; text-align:right;">
                                <div>{{ $selectedMonthDataBarang['total_realisasi_formatted'] }} / {{ $selectedMonthDataBarang['total_target_formatted'] }}</div>
                                <div style="font-weight:700; color:#304935;">({{ number_format($selectedMonthDataBarang['ratio'], 2, ',', '.') }}%)</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; font-size:.8rem; color:#516553;">
                            <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-forest); display:inline-block;"></span>Target</span>
                            <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-leaf); display:inline-block;"></span>Realisasi</span>
                        </div>

                        @if (!empty($selectedMonthDataBarang['teams']))
                            <div style="display:flex; gap:.8rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9; overflow-x:auto;">
                                @foreach ($selectedMonthDataBarang['teams'] as $team)
                                    <div style="width:auto; flex:0 0 auto; text-align:center;">
                                        <div style="display:flex; align-items:flex-end; justify-content:center; gap:.6rem; height:150px; margin-bottom:.45rem;">
                                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                <div style="width:56px; border-radius:0; height: {{ $team['target_bar_height'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 6px 14px rgba(31,107,69,.12); cursor:pointer;" title="Target: Rp {{ $team['target_formatted'] }}"></div>
                                                <span style="font-size:9px; color:#9aa8a0; line-height:1;">T</span>
                                            </div>
                                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                <div style="width:56px; border-radius:0; height: {{ $team['realisasi_bar_height'] }}px; background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%); box-shadow: 0 6px 14px rgba(77,159,96,.12); cursor:pointer;" title="Realisasi: Rp {{ $team['realisasi_formatted'] }}"></div>
                                                <span style="font-size:9px; color:#9aa8a0; line-height:1;">R</span>
                                            </div>
                                        </div>
                                        <div style="font-size:.75rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px; margin:0 auto;">{{ $team['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div style="display:grid; gap:.55rem; margin-top:1rem;">
                                @foreach ($selectedMonthDataBarang['teams'] as $team)
                                    <div>
                                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                            <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                            <span>{{ $team['realisasi_formatted'] }} / {{ $team['target_formatted'] }} ({{ number_format($team['ratio'], 2, ',', '.') }}%)</span>
                                        </div>
                                        <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                            <div style="height:100%; width: {{ $team['ratio'] }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="placeholder-chart" style="min-height:220px;">
                                <div>
                                    <div class="placeholder-badge">Data belum tersedia</div>
                                    <h5 style="margin:.75rem 0 .35rem; color:#304935;">Belanja Barang kosong di bulan ini</h5>
                                    <p style="margin:0; color:#5e6f60;">Template sudah disiapkan. Saat data Belanja Barang terisi, akan muncul otomatis.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="placeholder-chart">
                        <div>
                            <div class="placeholder-badge">Belum ada bulan dipilih</div>
                            <h5 style="margin:.75rem 0 .35rem; color:#304935;">Pilih bulan terlebih dahulu</h5>
                            <p style="margin:0; color:#5e6f60;">Begitu bulan dipilih, target dan realisasi per tim akan muncul di area ini.</p>
                        </div>
                    </div>
                @endif
            @else
                <div class="placeholder-chart">
                    <div>
                        <div class="placeholder-badge">Data belum tersedia</div>
                        <h5 style="margin:.75rem 0 .35rem; color:#304935;">Data bulanan belum ditemukan</h5>
                        <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke sini.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Belanja Pegawai</div>
                    <div class="panel-small">Target vs Realisasi untuk Belanja Pegawai.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalBelanjaPegawai" aria-label="Perjelas grafik Belanja Pegawai" title="Perjelas grafik">
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>

            <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-top:.75rem; margin-bottom:1rem;">
                <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                    <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Target</div>
                    <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaPegawai['total_target_formatted'] }}</div>
                </div>
                <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                    <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Realisasi</div>
                    <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaPegawai['total_realisasi_formatted'] }}</div>
                </div>
                <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                    <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Serapan Rata-rata</div>
                    <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartBelanjaPegawai['overall_ratio'], 2, ',', '.') }}%</div>
                </div>
            </div>

            @if (!empty($monthOptions) || !empty($selectedMonthDataPegawai))
                @if (!empty($selectedMonthDataPegawai))
                    <div style="margin-top:.75rem; padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.75rem; flex-wrap:wrap;">
                            <div>
                                <h5 style="margin:0; color:#304935; font-size:1.05rem; font-weight:700;">{{ $selectedMonthDataPegawai['label'] }}</h5>
                                <div style="font-size:.78rem; color:#7a8a7d;">Pilih bulan untuk menampilkan bulan tersebut.</div>
                            </div>
                            <div style="font-size:.8rem; color:#6b7c72; text-align:right;">
                                <div>{{ $selectedMonthDataPegawai['total_realisasi_formatted'] }} / {{ $selectedMonthDataPegawai['total_target_formatted'] }}</div>
                                <div style="font-weight:700; color:#304935;">({{ number_format($selectedMonthDataPegawai['ratio'], 2, ',', '.') }}%)</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; font-size:.8rem; color:#516553;">
                            <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:#38503f; display:inline-block;"></span>Target</span>
                            <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:#8bc19a; display:inline-block;"></span>Realisasi</span>
                        </div>

                        @if (!empty($selectedMonthDataPegawai['teams']))
                            <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9;">
                                @foreach ($selectedMonthDataPegawai['teams'] as $team)
                                    <div style="width:auto; flex:0 0 auto; text-align:center;">
                                        <div style="display:flex; align-items:flex-end; justify-content:center; gap:1rem; height:150px; margin-bottom:.45rem;">
                                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                <div class="chart-bar-target" style="width:72px; border-radius:0; height: {{ $team['target_bar_height'] }}px; background: linear-gradient(180deg, #38503f 0%, #4b6a56 100%); box-shadow: 0 8px 18px rgba(56,80,63,.12); cursor:pointer;" title="Target: Rp {{ $team['target_formatted'] }}"></div>
                                                <span style="font-size:10px; color:#9aa8a0; line-height:1;">T</span>
                                            </div>
                                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                <div class="chart-bar-realisasi" style="width:72px; border-radius:0; height: {{ $team['realisasi_bar_height'] }}px; background: linear-gradient(180deg, #8bc19a 0%, #6fa47f 100%); box-shadow: 0 8px 18px rgba(111,164,127,.12); cursor:pointer;" title="Realisasi: Rp {{ $team['realisasi_formatted'] }}"></div>
                                                <span style="font-size:10px; color:#9aa8a0; line-height:1;">R</span>
                                            </div>
                                        </div>
                                        <div style="font-size:.8rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; margin:0 auto;">{{ $team['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div style="display:grid; gap:.55rem; margin-top:1rem;">
                                @foreach ($selectedMonthDataPegawai['teams'] as $team)
                                    <div>
                                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                            <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                            <span>{{ $team['realisasi_formatted'] }} / {{ $team['target_formatted'] }} ({{ number_format($team['ratio'], 2, ',', '.') }}%)</span>
                                        </div>
                                        <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                            <div style="height:100%; width: {{ $team['ratio'] }}%; background: linear-gradient(90deg, #38503f 0%, #8bc19a 100%);"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="placeholder-chart" style="min-height:220px;">
                                <div>
                                    <div class="placeholder-badge">Data belum tersedia</div>
                                    <h5 style="margin:.75rem 0 .35rem; color:#304935;">Belanja Pegawai kosong di bulan ini</h5>
                                    <p style="margin:0; color:#5e6f60;">Template sudah disiapkan. Saat data Belanja Pegawai terisi, akan muncul otomatis.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="placeholder-chart">
                        <div>
                            <div class="placeholder-badge">Belum ada bulan dipilih</div>
                            @if (empty($monthOptions))
                                <h5 style="margin:.75rem 0 .35rem; color:#304935;">Data bulanan belum ditemukan</h5>
                                <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke sini.</p>
                            @else
                                <h5 style="margin:.75rem 0 .35rem; color:#304935;">Pilih bulan terlebih dahulu</h5>
                                <p style="margin:0; color:#5e6f60;">Begitu bulan dipilih, target dan realisasi Belanja Pegawai akan muncul di area ini.</p>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="placeholder-chart">
                    <div>
                        <div class="placeholder-badge">Belum ada bulan dipilih</div>
                        <h5 style="margin:.75rem 0 .35rem; color:#304935;">Data bulanan belum ditemukan</h5>
                        <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke sini.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Rekap Anggaran per Tim</div>
                    <div class="panel-small">Rekap anggaran tahunan per tim dari sheet workbook.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalRekapAnggaran" aria-label="Perjelas grafik Rekap Anggaran" title="Perjelas grafik">
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>
            @if (!empty($chartRekapAnggaranPerTim['teams']))
                <div style="padding:1rem 1rem 1.1rem;">
                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-bottom:1rem;">
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Anggaran</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['total_formatted'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Jumlah Tim</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['team_count'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Anggaran Tertinggi</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['highest_formatted'] }}</div>
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9;">
                        @foreach ($chartRekapAnggaranPerTim['teams'] as $team)
                            <div style="width:auto; flex:0 0 auto; text-align:center;">
                                <div style="display:flex; align-items:flex-end; justify-content:center; gap:0; height:180px; margin-bottom:.45rem;">
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                        <div class="chart-bar-target" style="width:72px; border-radius:0; height: {{ $team['bar_height'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 8px 18px rgba(31,107,69,.12); cursor:pointer;" title="{{ $team['label'] }}: Rp {{ $team['formatted'] }}"></div>
                                    </div>
                                </div>
                                <div style="font-size:.8rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100px; margin:0 auto;">{{ $team['label'] }}</div>
                                <div style="font-size:.78rem; color:#304935; font-weight:700; margin-top:.25rem;">{{ $team['formatted'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:grid; gap:.55rem; margin-top:1rem;">
                        @foreach ($chartRekapAnggaranPerTim['teams'] as $team)
                            <div>
                                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                    <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                    <span>{{ $team['formatted'] }}</span>
                                </div>
                                <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                    <div style="height:100%; width: {{ $team['bar_height'] / 1.6 }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="placeholder-chart">
                    <div>
                        <div class="placeholder-badge">Data belum tersedia</div>
                        <h5 style="margin:.75rem 0 .35rem; color:#304935;">Rekap anggaran per tim kosong</h5>
                        <p style="margin:0; color:#5e6f60;">Sheet rekap anggaran tidak ditemukan atau belum punya nilai yang bisa dibaca.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Laporan Penyerapan Anggaran</div>
                    <div class="panel-small">Progres penyerapan anggaran dari sheet workbook.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalLaporanPenyerapan" aria-label="Perjelas grafik Laporan Penyerapan" title="Perjelas grafik">
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>
            @if (!empty($chartLaporanPenyerapanAnggaran['available']) || !empty($chartLaporanPenyerapanAnggaran['realisasi']))
                <div style="padding:1rem;">
                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-bottom:1rem;">
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Anggaran Tersedia</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartLaporanPenyerapanAnggaran['available_formatted'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Realisasi s.d. April</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartLaporanPenyerapanAnggaran['realisasi_formatted'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Serapan</div>
                            <div style="font-size:1.1rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartLaporanPenyerapanAnggaran['ratio'], 2, ',', '.') }}%</div>
                        </div>
                    </div>

                    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; min-height:210px; padding:1rem 1rem .5rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb;">
                        <div style="flex:1; text-align:center;">
                            <div style="display:flex; align-items:flex-end; justify-content:center; height:160px; margin-bottom:.45rem;">
                                <div class="chart-bar-target" style="width:72px; border-radius:0; height: {{ $chartLaporanPenyerapanAnggaran['bar_height_available'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow:0 8px 18px rgba(31,107,69,.12); cursor:pointer;" title="Anggaran tersedia: Rp {{ $chartLaporanPenyerapanAnggaran['available_formatted'] }}"></div>
                            </div>
                            <div style="font-size:.85rem; color:#304935; font-weight:700;">Anggaran tersedia</div>
                        </div>
                        <div style="flex:1; text-align:center;">
                            <div style="display:flex; align-items:flex-end; justify-content:center; height:160px; margin-bottom:.45rem;">
                                <div style="width:72px; border-radius:0; height: {{ $chartLaporanPenyerapanAnggaran['bar_height_realisasi'] }}px; background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%); box-shadow:0 8px 18px rgba(77,159,96,.12); cursor:pointer;" title="Realisasi s.d. April: Rp {{ $chartLaporanPenyerapanAnggaran['realisasi_formatted'] }}"></div>
                            </div>
                            <div style="font-size:.85rem; color:#304935; font-weight:700;">Realisasi s.d. April</div>
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; font-size:.8rem; color:#516553; margin-bottom:.35rem;">
                            <span style="font-weight:700; color:#304935;">Progress penyerapan</span>
                            <span>{{ number_format($chartLaporanPenyerapanAnggaran['ratio'], 2, ',', '.') }}%</span>
                        </div>
                        <div style="height:10px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                            <div style="height:100%; width: {{ $chartLaporanPenyerapanAnggaran['ratio'] }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="placeholder-chart">
                    <div>
                        <div class="placeholder-badge">Data belum tersedia</div>
                        <h5 style="margin:.75rem 0 .35rem; color:#304935;">Laporan penyerapan anggaran kosong</h5>
                        <p style="margin:0; color:#5e6f60;">Sheet laporan tidak ditemukan atau belum punya nilai yang bisa dibaca.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Modal Belanja Barang - Expanded -->
<div class="modal fade" id="modalBelanjaBarang" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #1f6b45 0%, #2f8b59 100%); color: white; border: none;">
                <h5 class="modal-title fw-bold">Belanja Barang - Tampilan Besar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8faf9; max-height: 80vh; overflow-y: auto;">
                <div class="panel chart-card" style="margin: 0;">
                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-bottom:1.5rem;">
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Target</div>
                            <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaBarang['total_target_formatted'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Realisasi</div>
                            <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaBarang['total_realisasi_formatted'] }}</div>
                        </div>
                        <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Serapan Rata-rata</div>
                            <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartBelanjaBarang['overall_ratio'], 2, ',', '.') }}%</div>
                        </div>
                    </div>

                    @if (!empty($selectedMonthDataBarang))
                        <div style="padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb;">
                            <h5 style="margin:0 0 1rem; color:#304935; font-size:1.1rem; font-weight:700;">{{ $selectedMonthDataBarang['label'] }}</h5>
                            <div style="font-size:.8rem; color:#7a8a7d; margin-bottom:1rem;">{{ $selectedMonthDataBarang['total_realisasi_formatted'] }} / {{ $selectedMonthDataBarang['total_target_formatted'] }} ({{ number_format($selectedMonthDataBarang['ratio'], 2, ',', '.') }}%)</div>

                            <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; font-size:.8rem; color:#516553;">
                                <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-forest); display:inline-block;"></span>Target</span>
                                <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-leaf); display:inline-block;"></span>Realisasi</span>
                            </div>

                            @if (!empty($selectedMonthDataBarang['teams']))
                                <div style="display:flex; gap:.8rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9; overflow-x:auto; margin-bottom:1rem;">
                                    @foreach ($selectedMonthDataBarang['teams'] as $team)
                                        <div style="width:auto; flex:0 0 auto; text-align:center;">
                                            <div style="display:flex; align-items:flex-end; justify-content:center; gap:.6rem; height:200px; margin-bottom:.45rem;">
                                                <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                    <div class="chart-bar-target" style="width:56px; border-radius:0; height: {{ $team['target_bar_height'] * 1.2 }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 6px 14px rgba(31,107,69,.12); cursor:pointer;" title="Target: Rp {{ $team['target_formatted'] }}"></div>
                                                    <span style="font-size:9px; color:#9aa8a0; line-height:1;">T</span>
                                                </div>
                                                <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                    <div class="chart-bar-realisasi" style="width:56px; border-radius:0; height: {{ $team['realisasi_bar_height'] * 1.2 }}px; background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%); box-shadow: 0 6px 14px rgba(77,159,96,.12); cursor:pointer;" title="Realisasi: Rp {{ $team['realisasi_formatted'] }}"></div>
                                                    <span style="font-size:9px; color:#9aa8a0; line-height:1;">R</span>
                                                </div>
                                            </div>
                                            <div style="font-size:.75rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px; margin:0 auto;">{{ $team['label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div style="display:grid; gap:.55rem;">
                                    @foreach ($selectedMonthDataBarang['teams'] as $team)
                                        <div>
                                            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                                <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                                <span>{{ $team['realisasi_formatted'] }} / {{ $team['target_formatted'] }} ({{ number_format($team['ratio'], 2, ',', '.') }}%)</span>
                                            </div>
                                            <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                                <div style="height:100%; width: {{ $team['ratio'] }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="placeholder-chart">
                            <div>
                                <div class="placeholder-badge">Belum ada bulan dipilih</div>
                                <h5 style="margin:.75rem 0 .35rem; color:#304935;">Pilih bulan terlebih dahulu di dashboard</h5>
                                <p style="margin:0; color:#5e6f60;">Begitu bulan dipilih, target dan realisasi per tim akan muncul di area ini.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #f8faf9;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('div[style*="background: linear-gradient"]').forEach(bar => {
        const s = bar.getAttribute('style');
        if (s.includes('#1f6b45') && s.includes('#2f8b59')) {
            bar.classList.add('chart-bar-target');
        } else if (s.includes('#7bbd86') && s.includes('#4d9f60')) {
            bar.classList.add('chart-bar-realisasi');
        }
    });
});
</script>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const bars = document.querySelectorAll('div[style*="background: linear-gradient"]');
                bars.forEach(bar => {
                    const style = bar.getAttribute('style');
                    if (style.includes('#1f6b45') && style.includes('#2f8b59')) {
                        bar.classList.add('chart-bar-target');
                    } else if (style.includes('#7bbd86') && style.includes('#4d9f60')) {
                        bar.classList.add('chart-bar-realisasi');
                    }
                });
            });
            </script>
            </div>
        </div>
    </div>
</div>

<!-- Modal Belanja Pegawai - Expanded -->
<div class="modal fade" id="modalBelanjaPegawai" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #38503f 0%, #4b6a56 100%); color: white; border: none;">
                <h5 class="modal-title fw-bold">Belanja Pegawai - Tampilan Besar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8faf9; max-height: 80vh; overflow-y: auto;">
                <div class="panel chart-card" style="margin: 0;">
                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                        <div style="padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:90px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.4rem;">Total Target</div>
                            <div style="font-size:1.4rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaPegawai['total_target_formatted'] }}</div>
                        </div>
                        <div style="padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:90px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.4rem;">Total Realisasi</div>
                            <div style="font-size:1.4rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartBelanjaPegawai['total_realisasi_formatted'] }}</div>
                        </div>
                        <div style="padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:90px; display:flex; flex-direction:column; justify-content:center;">
                            <div style="font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.4rem;">Serapan Rata-rata</div>
                            <div style="font-size:1.4rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartBelanjaPegawai['overall_ratio'], 2, ',', '.') }}%</div>
                        </div>
                    </div>

                    @if (!empty($selectedMonthDataPegawai))
                        <div style="padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb;">
                            <h5 style="margin:0 0 1rem; color:#304935; font-size:1.1rem; font-weight:700;">{{ $selectedMonthDataPegawai['label'] }}</h5>
                            <div style="font-size:.8rem; color:#7a8a7d; margin-bottom:1rem;">{{ $selectedMonthDataPegawai['total_realisasi_formatted'] }} / {{ $selectedMonthDataPegawai['total_target_formatted'] }} ({{ number_format($selectedMonthDataPegawai['ratio'], 2, ',', '.') }}%)</div>

                            <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; font-size:.8rem; color:#516553;">
                                <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-forest); display:inline-block;"></span>Target</span>
                                <span style="display:inline-flex; align-items:center; gap:.4rem;"><span style="width:10px; height:10px; border-radius:50%; background:var(--chart-leaf); display:inline-block;"></span>Realisasi</span>
                            </div>

                            @if (!empty($selectedMonthDataPegawai['teams']))
                                <div style="display:flex; gap:.8rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9; overflow-x:auto; margin-bottom:1rem;">
                                    @foreach ($selectedMonthDataPegawai['teams'] as $team)
                                        <div style="width:auto; flex:0 0 auto; text-align:center;">
                                            <div style="display:flex; align-items:flex-end; justify-content:center; gap:.6rem; height:200px; margin-bottom:.45rem;">
                                                <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                    <div style="width:56px; border-radius:0; height: {{ $team['target_bar_height'] * 1.2 }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 6px 14px rgba(31,107,69,.12); cursor:pointer;" title="Target: Rp {{ $team['target_formatted'] }}"></div>
                                                    <span style="font-size:9px; color:#9aa8a0; line-height:1;">T</span>
                                                </div>
                                                <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                    <div style="width:56px; border-radius:0; height: {{ $team['realisasi_bar_height'] * 1.2 }}px; background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%); box-shadow: 0 6px 14px rgba(77,159,96,.12); cursor:pointer;" title="Realisasi: Rp {{ $team['realisasi_formatted'] }}"></div>
                                                    <span style="font-size:9px; color:#9aa8a0; line-height:1;">R</span>
                                                </div>
                                            </div>
                                            <div style="font-size:.75rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px; margin:0 auto;">{{ $team['label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div style="display:grid; gap:.55rem;">
                                    @foreach ($selectedMonthDataPegawai['teams'] as $team)
                                        <div>
                                            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                                <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                                <span>{{ $team['realisasi_formatted'] }} / {{ $team['target_formatted'] }} ({{ number_format($team['ratio'], 2, ',', '.') }}%)</span>
                                            </div>
                                            <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                                <div style="height:100%; width: {{ $team['ratio'] }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="placeholder-chart">
                            <div>
                                <div class="placeholder-badge">Belum ada bulan dipilih</div>
                                <h5 style="margin:.75rem 0 .35rem; color:#304935;">Pilih bulan terlebih dahulu di dashboard</h5>
                                <p style="margin:0; color:#5e6f60;">Begitu bulan dipilih, target dan realisasi per tim akan muncul di area ini.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #f8faf9;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rekap Anggaran per Tim - Expanded -->
<div class="modal fade" id="modalRekapAnggaran" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #5a6f64 0%, #7a8f84 100%); color: white; border: none;">
                <h5 class="modal-title fw-bold">Rekap Anggaran per Tim - Tampilan Besar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8faf9; max-height: 80vh; overflow-y: auto;">
                @if (!empty($chartRekapAnggaranPerTim['teams']))
                    <div class="panel chart-card" style="margin: 0;">
                        <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-bottom:1rem;">
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Total Anggaran</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['total_formatted'] }}</div>
                            </div>
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Jumlah Tim</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['team_count'] }}</div>
                            </div>
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Anggaran Tertinggi</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartRekapAnggaranPerTim['highest_formatted'] }}</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:1rem; align-items:flex-end; justify-content:flex-start; padding-bottom:.75rem; border-bottom:1px solid #e8efe9; overflow-x:auto; margin-bottom:1rem;">
                            @foreach ($chartRekapAnggaranPerTim['teams'] as $team)
                                <div style="width:auto; flex:0 0 auto; text-align:center;">
                                    <div style="display:flex; align-items:flex-end; justify-content:center; height:200px; margin-bottom:.45rem;">
                                        <div class="chart-bar-target" style="width:72px; border-radius:0; height: {{ $team['bar_height'] * 1.1 }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 6px 14px rgba(31,107,69,.12); cursor:pointer;" title="{{ $team['label'] }}: {{ $team['formatted'] }}"></div>
                                    </div>
                                    <div style="font-size:.75rem; color:#7a8a7d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100px; margin:0 auto;">{{ $team['label'] }}</div>
                                    <div style="font-size:.78rem; color:#304935; font-weight:700; margin-top:.25rem;">{{ $team['formatted'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div style="display:grid; gap:.55rem;">
                            @foreach ($chartRekapAnggaranPerTim['teams'] as $team)
                                <div>
                                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.25rem; font-size:.8rem; color:#5e6f60;">
                                        <strong style="color:#304935; font-size:.82rem;">{{ $team['label'] }}</strong>
                                        <span>{{ $team['formatted'] }}</span>
                                    </div>
                                    <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                        <div style="height:100%; width: {{ $team['bar_height'] / 1.6 }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="placeholder-chart">
                        <div>
                            <div class="placeholder-badge">Data belum tersedia</div>
                            <h5 style="margin:.75rem 0 .35rem; color:#304935;">Rekap anggaran per tim kosong</h5>
                            <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data rekap per tim bisa dipetakan ke sini.</p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #f8faf9;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Laporan Penyerapan Anggaran - Expanded -->
<div class="modal fade" id="modalLaporanPenyerapan" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #2d5a3d 0%, #4a7a56 100%); color: white; border: none;">
                <h5 class="modal-title fw-bold">Laporan Penyerapan Anggaran - Tampilan Besar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8faf9; max-height: 80vh; overflow-y: auto;">
                @if (!empty($chartLaporanPenyerapanAnggaran['available']) || !empty($chartLaporanPenyerapanAnggaran['realisasi']))
                    <div class="panel chart-card" style="margin: 0;">
                        <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:.8rem; margin-bottom:1rem;">
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Anggaran Tersedia</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartLaporanPenyerapanAnggaran['available_formatted'] }}</div>
                            </div>
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Realisasi</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ $chartLaporanPenyerapanAnggaran['realisasi_formatted'] }}</div>
                            </div>
                            <div style="padding:.85rem 1rem .9rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; box-shadow:0 8px 16px rgba(31,107,69,.05); min-height:74px; display:flex; flex-direction:column; justify-content:center;">
                                <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#7a8a7d; line-height:1; margin-bottom:.3rem;">Serapan</div>
                                <div style="font-size:1.2rem; font-weight:800; color:#203d2a; line-height:1.05;">{{ number_format($chartLaporanPenyerapanAnggaran['ratio'], 2, ',', '.') }}%</div>
                            </div>
                        </div>

                        <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; min-height:200px; padding:1rem; border:1px solid #dfeee1; border-radius:14px; background:#fbfefb; margin-bottom:1rem;">
                            <div style="flex:1; text-align:center;">
                                <div style="display:flex; align-items:flex-end; justify-content:center; height:150px; margin-bottom:.45rem;">
                                    <div class="chart-bar-target" style="width:72px; border-radius:0; height: {{ $chartLaporanPenyerapanAnggaran['bar_height_available'] * 0.8 }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow:0 6px 14px rgba(31,107,69,.12); cursor:pointer;" title="Anggaran tersedia: {{ $chartLaporanPenyerapanAnggaran['available_formatted'] }}"></div>
                                </div>
                                <div style="font-size:.8rem; color:#304935; font-weight:700;">Anggaran tersedia</div>
                            </div>
                            <div style="flex:1; text-align:center;">
                                <div style="display:flex; align-items:flex-end; justify-content:center; height:150px; margin-bottom:.45rem;">
                                    <div class="chart-bar-realisasi" style="width:72px; border-radius:0; height: {{ $chartLaporanPenyerapanAnggaran['bar_height_realisasi'] * 0.8 }}px; background: linear-gradient(180deg, #7bbd86 0%, #4d9f60 100%); box-shadow:0 6px 14px rgba(77,159,96,.12); cursor:pointer;" title="Realisasi: {{ $chartLaporanPenyerapanAnggaran['realisasi_formatted'] }}"></div>
                                </div>
                                <div style="font-size:.8rem; color:#304935; font-weight:700;">Realisasi</div>
                            </div>
                        </div>

                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; font-size:.8rem; color:#516553; margin-bottom:.35rem;">
                                <span style="font-weight:700; color:#304935;">Progress penyerapan</span>
                                <span>{{ number_format($chartLaporanPenyerapanAnggaran['ratio'], 2, ',', '.') }}%</span>
                            </div>
                            <div style="height:12px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                <div style="height:100%; width: {{ $chartLaporanPenyerapanAnggaran['ratio'] }}%; background: linear-gradient(90deg, var(--chart-forest) 0%, var(--chart-leaf) 100%);"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="placeholder-chart">
                        <div>
                            <div class="placeholder-badge">Data belum tersedia</div>
                            <h5 style="margin:.75rem 0 .35rem; color:#304935;">Laporan penyerapan anggaran kosong</h5>
                            <p style="margin:0; color:#5e6f60;">Sheet laporan tidak ditemukan atau belum punya nilai yang bisa dibaca.</p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #f8faf9;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>
            </div>
        </div>
    </div>
</div>

@endsection
