@extends('layouts.app')

@section('title', 'Keutata')

@section('subtitle', 'Data Excel yang berhasil dibaca akan muncul dengan gaya visual yang sama seperti dashboard utama.')

@section('page-actions')
<a href="#" class="btn-pill btn-outline-soft"><i class="bi bi-funnel me-2"></i>Filter</a>
<a href="#" class="btn-pill btn-primary-soft"><i class="bi bi-upload me-2"></i>Import File</a>
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

    @if($error)
        <div class="alert alert-danger border-0 rounded-4">
            <strong>Gagal:</strong> {{ $error }}
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
                        <th>Uraian KRO</th>
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
                            <td>{{ $row['uraian_kro'] }}</td>
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
