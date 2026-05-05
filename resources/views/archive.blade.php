@extends('layouts.app')

@section('title', 'Arsip File')

@section('subtitle', 'Daftar semua file Excel yang pernah diupload.')

@section('page-actions')
<a href="{{ route('overview') }}" class="btn-pill btn-outline-soft"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
@endsection

@section('content')
<style>
    .bulk-actions {
        display: none;
        padding: 1rem;
        background: #f7faf7;
        border-bottom: 1px solid #dfeee1;
        margin-bottom: 1rem;
        border-radius: 8px;
    }

    .bulk-actions.show {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .bulk-actions-info {
        flex: 1;
        font-size: 0.9rem;
        color: #304935;
        font-weight: 500;
    }
</style>

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

    <div class="bulk-actions" id="bulkActions">
        <div class="bulk-actions-info">
            <span id="selectedCount">0</span> file dipilih
        </div>
        <button type="button" class="btn-pill btn-outline-soft py-1 px-3" onclick="deselectAll()" style="font-size: 0.85rem;">
            Batalkan Pilihan
        </button>
        <button type="button" class="btn-pill py-1 px-3" style="font-size: 0.85rem; background: #c92a2a; color: white; border: none;" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
            <i class="bi bi-trash me-1"></i>Hapus Terpilih
        </button>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 40px;">
                        <input type="checkbox" id="selectAll" style="cursor: pointer; width: 18px; height: 18px;">
                    </th>
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
                    <td style="padding: 0.75rem; text-align: center;">
                        <input type="checkbox" class="file-checkbox" value="{{ $upload->id }}" style="cursor: pointer; width: 18px; height: 18px;">
                    </td>
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
                        <button type="button" class="btn-pill btn-outline-soft py-1 px-2" style="font-size: 0.85rem; color: #c92a2a;" data-bs-toggle="modal" data-bs-target="#deleteModal" data-file-id="{{ $upload->id }}" data-file-name="{{ $upload->file_name }}">
                            <i class="bi bi-trash me-1"></i>Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 1.5rem; text-align: center; color: #999;">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid #dfeee1;">
            <div class="modal-header" style="border-bottom: 1px solid #dfeee1; background: linear-gradient(180deg, #fbfefb 0%, #f5faf5 100%);">
                <h5 class="modal-title" id="deleteModalLabel" style="color: #304935; font-weight: 600;">Hapus File Arsip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="color: #c92a2a; font-size: 1.5rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.5rem 0; color: #304935; font-weight: 500;">Anda yakin ingin menghapus file ini?</p>
                        <p style="margin: 0; color: #5e6f60; font-size: 0.9rem;">File: <strong id="deleteFileName"></strong></p>
                        <p style="margin: 0.5rem 0 0 0; color: #c92a2a; font-size: 0.85rem;">
                            <i class="bi bi-info-circle me-1"></i>Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #fbfefb; padding: 1rem;">
                <button type="button" class="btn-pill btn-outline-soft" data-bs-dismiss="modal" style="margin-right: 0.5rem;">
                    Batal
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-pill" style="background: #c92a2a; color: white; border: none; font-weight: 500;">
                        <i class="bi bi-trash me-1"></i>Hapus File
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const fileCheckboxes = document.querySelectorAll('.file-checkbox');
    const bulkActionsDiv = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const deleteFileName = document.getElementById('deleteFileName');
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');

    // Update bulk actions visibility and count
    function updateBulkActions() {
        const selectedCount = Array.from(fileCheckboxes).filter(cb => cb.checked).length;
        selectedCountSpan.textContent = selectedCount;
        
        if (selectedCount > 0) {
            bulkActionsDiv.classList.add('show');
        } else {
            bulkActionsDiv.classList.remove('show');
        }

        // Update selectAll checkbox state
        selectAllCheckbox.checked = selectedCount === fileCheckboxes.length && fileCheckboxes.length > 0;
    }

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        fileCheckboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox change
    fileCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    // Single delete modal
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const fileId = button.getAttribute('data-file-id');
        const fileName = button.getAttribute('data-file-name');

        deleteFileName.textContent = fileName;
        deleteForm.action = '{{ route("archive.delete", ":id") }}'.replace(':id', fileId);
    });

    // Bulk delete modal
    bulkDeleteModal.addEventListener('show.bs.modal', function() {
        const selectedIds = Array.from(fileCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        const selectedCount = document.getElementById('bulkDeleteCount');
        selectedCount.textContent = selectedIds.length;

        // Set hidden input with IDs
        const idsInput = document.getElementById('deleteIds');
        idsInput.value = selectedIds.join(',');
    });

    // Initial update
    updateBulkActions();
});

function deselectAll() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
    
    const bulkActionsDiv = document.getElementById('bulkActions');
    bulkActionsDiv.classList.remove('show');
    document.getElementById('selectedCount').textContent = '0';
}
</script>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid #dfeee1;">
            <div class="modal-header" style="border-bottom: 1px solid #dfeee1; background: linear-gradient(180deg, #fbfefb 0%, #f5faf5 100%);">
                <h5 class="modal-title" id="bulkDeleteModalLabel" style="color: #304935; font-weight: 600;">Hapus Banyak File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="color: #c92a2a; font-size: 1.5rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.5rem 0; color: #304935; font-weight: 500;">Hapus <span id="bulkDeleteCount">0</span> file yang dipilih?</p>
                        <p style="margin: 0; color: #5e6f60; font-size: 0.9rem;">File ini akan dihapus dari arsip dan storage.</p>
                        <p style="margin: 0.5rem 0 0 0; color: #c92a2a; font-size: 0.85rem;">
                            <i class="bi bi-info-circle me-1"></i>Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #fbfefb; padding: 1rem;">
                <button type="button" class="btn-pill btn-outline-soft" data-bs-dismiss="modal" style="margin-right: 0.5rem;">
                    Batal
                </button>
                <form id="bulkDeleteForm" method="POST" action="{{ route('archive.bulk-delete') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" id="deleteIds" name="ids" value="">
                    <button type="submit" class="btn-pill" style="background: #c92a2a; color: white; border: none; font-weight: 500;">
                        <i class="bi bi-trash me-1"></i>Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Single Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid #dfeee1;">
            <div class="modal-header" style="border-bottom: 1px solid #dfeee1; background: linear-gradient(180deg, #fbfefb 0%, #f5faf5 100%);">
                <h5 class="modal-title" id="deleteModalLabel" style="color: #304935; font-weight: 600;">Hapus File Arsip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="color: #c92a2a; font-size: 1.5rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.5rem 0; color: #304935; font-weight: 500;">Anda yakin ingin menghapus file ini?</p>
                        <p style="margin: 0; color: #5e6f60; font-size: 0.9rem;">File: <strong id="deleteFileName"></strong></p>
                        <p style="margin: 0.5rem 0 0 0; color: #c92a2a; font-size: 0.85rem;">
                            <i class="bi bi-info-circle me-1"></i>Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dfeee1; background: #fbfefb; padding: 1rem;">
                <button type="button" class="btn-pill btn-outline-soft" data-bs-dismiss="modal" style="margin-right: 0.5rem;">
                    Batal
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-pill" style="background: #c92a2a; color: white; border: none; font-weight: 500;">
                        <i class="bi bi-trash me-1"></i>Hapus File
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
