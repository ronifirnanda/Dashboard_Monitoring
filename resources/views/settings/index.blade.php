@extends('layouts.app')

@section('title', 'Pengaturan')

@section('subtitle', 'Kelola konfigurasi aplikasi monitoring Anda.')

@section('content')
<div class="settings-container">
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="settings-layout">
    <div class="settings-card settings-main-card">
        <div class="settings-header">
            <div>
                <h3 class="settings-title">Google Sheets Configuration</h3>
                <p class="settings-description">Atur ID spreadsheet Google Sheets yang akan digunakan untuk sinkronisasi data.</p>
            </div>
            <i class="bi bi-google settings-icon"></i>
        </div>

        <form action="{{ route('admin.settings.update-google-sheets') }}" method="POST" class="settings-form">
            @csrf

            <div class="form-group">
                <label for="spreadsheet_id" class="form-label">Spreadsheet ID</label>
                <div class="input-group">
                    <input 
                        type="text" 
                        class="form-control @error('spreadsheet_id') is-invalid @enderror" 
                        id="spreadsheet_id" 
                        name="spreadsheet_id" 
                        value="{{ $googleSheetsId }}" 
                        placeholder="Masukkan ID dari URL Google Sheets Anda"
                        required
                    >
                    <button class="btn btn-outline-primary" type="button" id="copyBtn" title="Copy to clipboard">
                        <i class="bi bi-clipboard me-1"></i>Copy
                    </button>
                </div>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>Cari ID dari URL: 
                    <code>https://docs.google.com/spreadsheets/d/<strong>ID_DISINI</strong>/edit</code>
                </div>
                @error('spreadsheet_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                </button>
                <a href="{{ route('overview') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>

    <div class="settings-card settings-side-card">
    <div class="settings-info">
        <div class="info-box">
            <i class="bi bi-lightbulb"></i>
            <div>
                <h5>Tips</h5>
                <p>ID spreadsheet akan tersimpan di database aplikasi. Pastikan file Google Sheets Anda dapat diakses oleh service account yang telah dikonfigurasi.</p>
            </div>
        </div>

        <div class="info-box" style="margin-top: 1rem;">
            <i class="bi bi-shield-check"></i>
            <div>
                <h5>Cara Share Spreadsheet</h5>
                <ol style="margin: 0; padding-left: 1.25rem; color: var(--muted); font-size: 0.9rem;">
                    <li>Buka Google Sheets Anda</li>
                    <li>Klik tombol <strong>"Share"</strong> (pojok kanan atas)</li>
                    <li>Paste email berikut:<br><code style="background: #f5f8f6; padding: 0.5rem; display: inline-block; margin: 0.5rem 0; border-radius: 0.35rem;">monitoring-rpd-sync@monitoring-rpd-496001.iam.gserviceaccount.com</code></li>
                    <li>Pilih permission <strong>"Editor"</strong></li>
                    <li>Klik <strong>"Share"</strong></li>
                </ol>
            </div>
        </div>
    </div>
    </div>
</div>

<style>
    .settings-container {
        max-width: none;
        width: 100%;
        margin: 0;
    }

    .settings-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.95fr);
        gap: 1.5rem;
        align-items: start;
    }

    .settings-card {
        background: var(--surface);
        border: 1px solid #e6ece6;
        border-radius: 1.25rem;
        padding: 2rem;
        box-shadow: 0 10px 28px rgba(24, 59, 40, 0.05);
        margin-bottom: 0;
    }

    .settings-main-card,
    .settings-side-card {
        min-width: 0;
    }

    .settings-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #edf2ed;
    }

    .settings-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .settings-description {
        color: var(--muted);
        font-size: 0.95rem;
        margin: 0;
    }

    .settings-icon {
        font-size: 2rem;
        color: var(--primary);
        opacity: 0.3;
    }

    .settings-form {
        margin: 0;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .input-group .form-control {
        border: 1px solid #e6ece6;
        border-radius: 0.75rem 0 0 0.75rem;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    .input-group .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(31, 107, 69, 0.1);
    }

    .input-group .btn-outline-primary {
        border: 1px solid #e6ece6;
        border-left: none;
        border-radius: 0 0.75rem 0.75rem 0;
        color: var(--primary);
        font-weight: 500;
    }

    .input-group .btn-outline-primary:hover {
        background-color: var(--primary-soft);
        border-color: var(--primary);
    }

    .form-text {
        margin-top: 0.5rem;
        color: var(--muted);
        font-size: 0.875rem;
    }

    .form-text code {
        background-color: #f5f8f6;
        padding: 0.25rem 0.5rem;
        border-radius: 0.35rem;
        color: var(--text);
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #edf2ed;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary {
        background-color: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-2);
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(31, 107, 69, 0.2);
    }

    .btn-secondary {
        background-color: transparent;
        color: var(--text);
        border: 1px solid #e6ece6;
    }

    .btn-secondary:hover {
        background-color: #f8fbf8;
        border-color: var(--primary);
    }

    .settings-info {
        margin-top: 0;
        display: grid;
        gap: 1rem;
    }

    .info-box {
        display: flex;
        gap: 1rem;
        padding: 1.25rem;
        background-color: rgba(31, 107, 69, 0.05);
        border: 1px solid rgba(31, 107, 69, 0.1);
        border-radius: 0.75rem;
    }

    .info-box i {
        font-size: 1.5rem;
        color: var(--primary);
        flex: 0 0 auto;
        margin-top: 0.25rem;
    }

    .info-box h5 {
        margin: 0 0 0.5rem 0;
        color: var(--text);
        font-weight: 600;
    }

    .info-box p {
        margin: 0;
        color: var(--muted);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .info-box ol {
        margin: 0;
        padding-left: 1.25rem;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .info-box li {
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }

    .info-box code {
        background: #f5f8f6;
        padding: 0.5rem;
        display: inline-block;
        margin: 0.5rem 0;
        border-radius: 0.35rem;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        word-break: break-all;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .alert {
        border: none;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #721c24;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.1);
        color: #155724;
    }

    @media (max-width: 992px) {
        .settings-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .settings-card {
            padding: 1.25rem;
        }

        .settings-header {
            flex-direction: column;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<script>
    document.getElementById('copyBtn')?.addEventListener('click', function() {
        const input = document.getElementById('spreadsheet_id');
        if (input.value) {
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = this;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                }, 2000);
            });
        }
    });
</script>
@endsection
