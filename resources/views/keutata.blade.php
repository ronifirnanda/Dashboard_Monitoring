@extends('layouts.app')

@section('title', 'Keutata')

@section('subtitle', 'Upload file Excel baru, sistem akan mempelajari struktur sheet terlebih dahulu, lalu menampilkan data yang berhasil dikenali.')

@section('page-actions')
<a href="#" class="btn-pill btn-outline-soft"><i class="bi bi-funnel me-2"></i>Filter</a>
@endsection

@section('content')
<div class="panel table-card">
    <div class="panel-header">
        <div>
            <div class="panel-title">Daftar Keutata Monitoring</div>
            <div class="panel-small">Data dari file Excel terbaru.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if(!empty($fileName))
                <span class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ $fileName }}</span>
            @endif
            <span class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-list-check me-2"></i>{{ count($rows) }} Rows</span>
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

    @if(!empty($analysis))
        <div class="row g-3 mb-4">
            @foreach($analysis as $sheetAnalysis)
                <div class="col-12 col-lg-4">
                    <div class="reminder-card h-100">
                        <div>
                            <h4 class="mb-2">{{ $sheetAnalysis['sheet'] }}</h4>
                            <p class="mb-2">Mode: {{ $sheetAnalysis['mode'] === 'detected' ? 'struktur terdeteksi' : 'fallback template' }}</p>
                            <p class="mb-2">Header baris: {{ $sheetAnalysis['header_row'] }}</p>
                            <p class="mb-0">Kolom dikenali: {{ $sheetAnalysis['recognized_columns'] }} | Baris data: {{ $sheetAnalysis['rows_found'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(empty($rows))
        <div class="reminder-card">
            <div>
                <h4>Belum ada data</h4>
                <p>Silahkan import file Excel terlebih dahulu agar tabel terisi.</p>
            </div>
        </div>
    @else
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
                            <td>{{ $row['nominal_rpd'] }}</td>
                            <td>{{ $row['deviasi'] }}</td>
                            <td>{{ $row['uraian'] }}</td>
                            <td>{{ $row['nominal_pengajuan'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
