@extends('layouts.app')

@section('title', 'Overview')

@section('subtitle', 'Plan, prioritize, and monitor your tasks with a calmer, greener dashboard.')

@section('page-actions')
<a href="#" class="btn-pill btn-primary-soft"><i class="bi bi-plus-lg me-2"></i>Add Project</a>
<a href="#" class="btn-pill btn-outline-soft"><i class="bi bi-upload me-2"></i>Import Data</a>
@endsection

@section('content')
@if ($fileName)
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <strong>File Excel dimuat:</strong> {{ $fileName }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Upload Form -->
<form action="{{ route('keutata.import') }}" method="POST" enctype="multipart/form-data" class="panel mb-4">
    @csrf
    <div class="panel-header">
        <div>
            <div class="panel-title">Upload Excel Baru</div>
            <div class="panel-small">Sistem akan mendeteksi struktur dan menampilkan data secara otomatis.</div>
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
            <div class="flex-grow-1">
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
        <small class="text-muted mt-3 d-block">File Excel akan dianalisis, header terdeteksi secara otomatis, dan data ditampilkan di kedua halaman.</small>
    </div>
</form>

<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="label">Total Projects</div>
        <div class="value">24</div>
        <div class="trend"><i class="bi bi-graph-up-arrow"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Ended Projects</div>
        <div class="value">10</div>
        <div class="trend"><i class="bi bi-arrow-up-right"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Running Projects</div>
        <div class="value">12</div>
        <div class="trend"><i class="bi bi-arrow-up-right"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Pending Project</div>
        <div class="value">2</div>
        <div class="trend"><i class="bi bi-dot"></i> On discuss</div>
    </div>
</div>

<div class="section-grid">
    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Target vs Realisasi</div>
                <div class="panel-small">Pilih bulan dulu, lalu akan tampil untuk bulan tersebut.</div>
            </div>
            <button type="button" class="icon-btn" style="width:36px;height:36px;"><i class="bi bi-arrow-up-right"></i></button>
        </div>

        @if (!empty($chart['months']))
            <form method="GET" action="{{ route('overview') }}" style="margin-bottom:1rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:end;">
                <div style="min-width:220px; flex:1;">
                    <label for="chart_month" class="form-label fw-semibold mb-2">Pilih Bulan</label>
                    <select name="chart_month" id="chart_month" class="form-select rounded-4">
                        <option value="">-- pilih bulan --</option>
                        @foreach ($chart['months'] as $month)
                            <option value="{{ $month['label'] }}" @selected(($selectedMonth ?? '') === $month['label'])>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-pill btn-primary-soft border-0">Tampilkan</button>
                </div>
            </form>

            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div style="flex:1; min-width:180px; background:#f7faf7; border:1px solid #dfeee1; border-radius:10px; padding:.75rem;">
                    <div style="font-size:.78rem; color:#5e6f60;">Total Target</div>
                    <div style="font-weight:700; color:#284b2f;">{{ $chart['total_target_formatted'] }}</div>
                </div>
                <div style="flex:1; min-width:180px; background:#f7faf7; border:1px solid #dfeee1; border-radius:10px; padding:.75rem;">
                    <div style="font-size:.78rem; color:#5e6f60;">Total Realisasi</div>
                    <div style="font-weight:700; color:#284b2f;">{{ $chart['total_realisasi_formatted'] }}</div>
                </div>
                <div style="flex:1; min-width:180px; background:#f7faf7; border:1px solid #dfeee1; border-radius:10px; padding:.75rem;">
                    <div style="font-size:.78rem; color:#5e6f60;">Serapan Rata-rata</div>
                    <div style="font-weight:700; color:#284b2f;">{{ number_format($chart['overall_ratio'], 2, ',', '.') }}%</div>
                </div>
            </div>

            <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; font-size:.82rem; color:#516553;">
                <div style="display:flex; align-items:center; gap:.45rem;"><span style="width:12px; height:12px; border-radius:999px; background:#1f6b45; display:inline-block;"></span>Target</div>
                <div style="display:flex; align-items:center; gap:.45rem;"><span style="width:12px; height:12px; border-radius:999px; background:#3f8f51; display:inline-block;"></span>Realisasi</div>
            </div>

            @if (!empty($selectedMonthData))
                <div style="border:1px solid #dfeee1; border-radius:12px; padding:1rem; background:#fbfefb;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.8rem; flex-wrap:wrap;">
                        <h5 style="margin:0; color:#304935;">{{ $selectedMonthData['label'] }}</h5>
                        <div style="font-size:.82rem; color:#5e6f60;">
                            {{ $selectedMonthData['total_realisasi_formatted'] }} / {{ $selectedMonthData['total_target_formatted'] }}
                            ({{ number_format($selectedMonthData['ratio'], 2, ',', '.') }}%)
                        </div>
                    </div>

                    <div class="chart-bars" style="grid-template-columns: repeat(auto-fit, minmax(88px, 1fr)); height: 190px; align-items:flex-end; margin-bottom:.8rem;">
                        @foreach ($selectedMonthData['teams'] as $team)
                            <div class="bar-wrap" title="{{ $team['label'] }} | Target: {{ $team['target_formatted'] }} | Realisasi: {{ $team['realisasi_formatted'] }}">
                                <div style="display:flex; align-items:flex-end; justify-content:center; gap:8px; width:100%; height:160px;">
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:6px; flex:1;">
                                        <div class="bar filled" style="width:100%; height: {{ $team['target_bar_height'] }}px; background: linear-gradient(180deg, #1f6b45 0%, #2f8b59 100%);"></div>
                                        <span style="font-size:10px; color:#6b7c72;">T</span>
                                    </div>
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:6px; flex:1;">
                                        <div class="bar filled" style="width:100%; height: {{ $team['realisasi_bar_height'] }}px; background: linear-gradient(180deg, #77b66d 0%, #4d9f60 100%);"></div>
                                        <span style="font-size:10px; color:#6b7c72;">R</span>
                                    </div>
                                </div>
                                <div class="day" style="max-width:72px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $team['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:grid; gap:.45rem;">
                        @foreach ($selectedMonthData['teams'] as $team)
                            <div>
                                <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.8rem; color:#516553; margin-bottom:.2rem;">
                                    <strong style="color:#304935;">{{ $team['label'] }}</strong>
                                    <span>{{ $team['realisasi_formatted'] }} / {{ $team['target_formatted'] }} ({{ number_format($team['ratio'], 2, ',', '.') }}%)</span>
                                </div>
                                <div style="height:8px; border-radius:999px; background:#e8efe9; overflow:hidden;">
                                    <div style="height:100%; width: {{ $team['ratio'] }}%; background: linear-gradient(90deg, #3f8f51 0%, #77b66d 100%);"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="reminder-card" style="margin-top:.5rem;">
                    <div>
                        <h4>Pilih bulan terlebih dahulu</h4>
                        <p>Setelah bulan dipilih, target vs realisasi per tim akan ditampilkan di sini.</p>
                    </div>
                </div>
            @endif
        @else
            <div class="reminder-card" style="margin-top:.5rem;">
                <div>
                    <h4>Data bulanan belum ditemukan</h4>
                    <p>Ini hanya menampilkan sheet yang nama-nya bulan (contoh: Februari, Maret, April), lalu merinci target/realisasi per tim.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Reminders</div>
                <div class="panel-small">Today agenda</div>
            </div>
        </div>

        <div class="reminder-card">
            <div>
                <h4>Meeting with Arc Company</h4>
                <p>Time: 02:00 pm - 04:00 pm</p>
            </div>
            <a href="#" class="btn-pill btn-primary-soft text-center w-100"><i class="bi bi-camera-video me-2"></i>Start Meeting</a>
        </div>
    </div>
</div>

@if (count($rows) > 0)
<!-- Excel Data Section -->
<div class="panel" style="margin-top: 2rem;">
    <div class="panel-header">
        <div>
            <div class="panel-title">Data dari Excel</div>
            <div class="panel-small">{{ count($rows) }} baris data dari file yang diupload</div>
        </div>
        <a href="{{ route('keutata') }}" class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-eye me-2"></i>Lihat Detail</a>
    </div>

    @if (count($analysis) > 0)
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        @foreach ($analysis as $item)
        <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem; background: #f9f9f9;">
            <h6 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #666;">
                <i class="bi bi-table me-2"></i>{{ $item['sheet'] ?? 'Sheet' }}
            </h6>
            <div style="font-size: 0.85rem; color: #777; line-height: 1.6;">
                <div><strong>Header:</strong> Baris {{ $item['header_row'] ?? '?' }}</div>
                <div><strong>Kolom:</strong> {{ implode(', ', $item['matched_columns'] ?? []) }}</div>
                <div><strong>Mode:</strong> <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; background: {{ $item['mode'] === 'detected' ? '#d4edda' : '#fff3cd' }}; font-size: 0.75rem;">{{ $item['mode'] === 'detected' ? 'struktur terdeteksi' : 'fallback mode' }}</span></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">No.</th>
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Sheet</th>
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Tim</th>
                    <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Nominal RPD</th>
                    <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Deviasi 5%</th>
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Uraian</th>
                    <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Nominal Pengajuan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                <tr style="border-bottom: 1px solid #eee; {{ ($index + 1) % 2 === 0 ? 'background: #fafafa;' : '' }}">
                    <td style="padding: 0.75rem;">{{ $row['no'] ?? ($index + 1) }}</td>
                    <td style="padding: 0.75rem;">{{ $row['sheet'] ?? '-' }}</td>
                    <td style="padding: 0.75rem;">{{ $row['tim_display'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: right;">{{ $row['nominal_rpd'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: right;">{{ $row['deviasi'] ?? '-' }}</td>
                    <td style="padding: 0.75rem;">{{ $row['uraian'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; text-align: right;">{{ $row['nominal_pengajuan'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 1.5rem; text-align: center; color: #999;">
                        Tidak ada data yang ditemukan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel" style="margin-top: 2rem;">
    <div class="panel-header">
        <div>
            <div class="panel-title">Project</div>
            <div class="panel-small">Latest items from the dashboard queue.</div>
        </div>
        <a href="#" class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-plus-lg me-2"></i>New</a>
    </div>

    <div class="project-list">
        <div class="project-item">
            <div class="project-dot" style="background:#3866ff;"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="project-text">
                <strong>Develop API Endpoints</strong>
                <span>Due date: Nov 26, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#3bb3aa;"><i class="bi bi-circle-half"></i></div>
            <div class="project-text">
                <strong>Onboarding Flow</strong>
                <span>Due date: Nov 28, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#1f6b45;"><i class="bi bi-kanban-fill"></i></div>
            <div class="project-text">
                <strong>Build Dashboard</strong>
                <span>Due date: Nov 30, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#ffb02e;"><i class="bi bi-upload"></i></div>
            <div class="project-text">
                <strong>Optimize Page Load</strong>
                <span>Due date: Dec 2, 2024</span>
            </div>
        </div>
    </div>
</div>
@endsection
