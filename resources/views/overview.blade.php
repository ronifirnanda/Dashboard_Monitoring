@extends('layouts.app')

@section('title', 'Overview')

@section('subtitle', 'Dashboard ringkas untuk 4 area grafik, dengan Grafik 1 dan 2 khusus belanja per bulan.')

@section('page-actions')
<a href="#charts" class="btn-pill btn-primary-soft"><i class="bi bi-bar-chart-line me-2"></i>Lihat Grafik</a>
<a href="#upload" class="btn-pill btn-outline-soft"><i class="bi bi-upload me-2"></i>Upload Excel</a>
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
</style>

@if(auth()->check() && auth()->user()->role === 'admin')
    <form id="upload" action="{{ route('admin.keutata.import') }}" method="POST" enctype="multipart/form-data" class="panel mb-4">
        @csrf
        <div class="panel-header">
            <div>
                <div class="panel-title">Upload Excel Baru</div>
                <div class="panel-small">Bagian ini dipertahankan supaya data dashboard tetap bisa dimuat dari Excel.</div>
            </div>
        </div>

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

        <div style="padding: 1.5rem;">
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
            <small class="text-muted mt-3 d-block">File Excel akan dianalisis, header terdeteksi otomatis, lalu dipakai oleh grafik yang ada di dashboard.</small>
        </div>
    </form>

    <div class="mt-3" style="display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;">
        <form action="{{ route('admin.keutata.sync-google-sheet') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn-pill btn-outline-soft border-0">
                <i class="bi bi-cloud-arrow-down me-2"></i>Sync dari Google Sheets
            </button>
        </form>
        <small class="text-muted">Gunakan tombol ini untuk ambil data terbaru dari spreadsheet private.</small>
    </div>
@endif

<section id="charts" class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Dashboard Grafik</div>
            <div class="panel-small">Grafik 1 menampilkan Belanja Barang, Grafik 2 menampilkan Belanja Pegawai.</div>
        </div>
    </div>

    <div style="padding: 0 1.25rem 1rem 1.25rem;">
        <form method="GET" action="{{ route('overview') }}" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:end;">
            @if(request('upload'))
                <input type="hidden" name="upload" value="{{ request('upload') }}">
            @endif
            <div style="min-width:220px; flex:1; max-width:360px;">
                <label for="chart_month" class="form-label fw-semibold mb-2">Filter Bulan Grafik</label>
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
                    <div class="panel-title">Grafik 1 - Belanja Barang</div>
                    <div class="panel-small">Grafik Target vs Realisasi untuk Belanja Barang.</div>
                </div>
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
                                <div style="font-size:.78rem; color:#7a8a7d;">Pilih bulan untuk menampilkan grafik bulan tersebut.</div>
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
                                    <p style="margin:0; color:#5e6f60;">Template sudah disiapkan. Saat data Belanja Barang terisi, grafik akan muncul otomatis.</p>
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
                        <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke grafik ini.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Grafik 2 - Belanja Pegawai</div>
                    <div class="panel-small">Grafik Target vs Realisasi untuk Belanja Pegawai.</div>
                </div>
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
                                <div style="font-size:.78rem; color:#7a8a7d;">Pilih bulan untuk menampilkan grafik bulan tersebut.</div>
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
                                                <div style="width:72px; border-radius:0; height: {{ $team['target_bar_height'] }}px; background: linear-gradient(180deg, #38503f 0%, #4b6a56 100%); box-shadow: 0 8px 18px rgba(56,80,63,.12); cursor:pointer;" title="Target: Rp {{ $team['target_formatted'] }}"></div>
                                                <span style="font-size:10px; color:#9aa8a0; line-height:1;">T</span>
                                            </div>
                                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px; flex:0 0 auto;">
                                                <div style="width:72px; border-radius:0; height: {{ $team['realisasi_bar_height'] }}px; background: linear-gradient(180deg, #8bc19a 0%, #6fa47f 100%); box-shadow: 0 8px 18px rgba(111,164,127,.12); cursor:pointer;" title="Realisasi: Rp {{ $team['realisasi_formatted'] }}"></div>
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
                                    <p style="margin:0; color:#5e6f60;">Template sudah disiapkan. Saat data Belanja Pegawai terisi, grafik akan muncul otomatis.</p>
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
                                <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke grafik ini.</p>
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
                        <p style="margin:0; color:#5e6f60;">Upload file Excel yang sesuai agar data bulanan bisa dipetakan ke grafik ini.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="panel chart-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Grafik 3 - Rekap Anggaran per Tim</div>
                    <div class="panel-small">Rekap anggaran tahunan per tim dari sheet workbook.</div>
                </div>
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
                                        <div style="width:72px; border-radius:0; height: {{ $team['bar_height'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow: 0 8px 18px rgba(31,107,69,.12); cursor:pointer;" title="{{ $team['label'] }}: Rp {{ $team['formatted'] }}"></div>
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
                    <div class="panel-title">Grafik 4 - Laporan Penyerapan Anggaran</div>
                    <div class="panel-small">Progres penyerapan anggaran dari sheet workbook.</div>
                </div>
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
                                <div style="width:72px; border-radius:0; height: {{ $chartLaporanPenyerapanAnggaran['bar_height_available'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%); box-shadow:0 8px 18px rgba(31,107,69,.12); cursor:pointer;" title="Anggaran tersedia: Rp {{ $chartLaporanPenyerapanAnggaran['available_formatted'] }}"></div>
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

@endsection
