@extends('layouts.app')

@section('title', 'Arsip File')

@section('subtitle', 'Daftar semua file Excel yang pernah diupload.')

@section('page-actions')
<a href="{{ route('overview') }}" class="btn-pill btn-outline-soft"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Arsip File Excel</div>
            <div class="panel-small">Total {{ $uploads->total() }} file tersimpan.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4" style="margin: 1rem;">
            <strong>Berhasil:</strong> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4" style="margin: 1rem;">
            <strong>Gagal:</strong> {{ session('error') }}
        </div>
    @endif

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">No.</th>
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Nama File</th>
                    <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Jumlah Baris</th>
                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Tanggal Upload</th>
                    <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($uploads as $index => $upload)
                <tr style="border-bottom: 1px solid #eee; {{ ($index + 1) % 2 === 0 ? 'background: #fafafa;' : '' }}">
                    <td style="padding: 0.75rem;">{{ ($uploads->currentPage() - 1) * $uploads->perPage() + $index + 1 }}</td>
                    <td style="padding: 0.75rem;">
                        <i class="bi bi-file-earmark-spreadsheet" style="margin-right: 0.5rem; color: #28a745;"></i>
                        {{ $upload->file_name }}
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #e7f3ff; border-radius: 4px; font-size: 0.85rem;">
                            {{ $upload->rows_count }} baris
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        {{ $upload->created_at->format('d M Y, H:i') }}
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ route('archive.load', $upload->id) }}" class="btn-pill btn-primary-soft py-1 px-2" style="font-size: 0.85rem;">
                            <i class="bi bi-cloud-download me-1"></i>Muat
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 1.5rem; text-align: center; color: #999;">
                        Belum ada file yang diupload
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($uploads->total() > 0)
    <div style="padding: 1rem; border-top: 1px solid #eee; margin-top: 1rem;">
        {{ $uploads->links() }}
    </div>
    @endif
</div>
@endsection
