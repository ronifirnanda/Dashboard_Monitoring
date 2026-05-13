@extends('layouts.app')

@section('title', 'Edit Profil')

@section('subtitle', 'Perbarui informasi akun Anda')

@section('content')
<div class="panel profile-panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Edit Profil</div>
            <div class="panel-small">Perbarui nama, email, password, dan foto profil Anda.</div>
        </div>
        <div class="panel-actions">
            <a href="{{ route('overview') }}" class="btn-pill btn-outline-soft">Kembali</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4" style="margin: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="profile-layout">
        <div class="profile-main card-surface">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
                @csrf
                @method('PUT')

                <div class="profile-hero">
                    <div class="profile-avatar-wrap">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="avatar" class="profile-avatar">
                        @else
                            <div class="profile-avatar profile-avatar-fallback">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        @endif
                    </div>

                    <div class="profile-hero-copy">
                        <div class="profile-name">{{ $user->name }}</div>
                        <div class="profile-email">{{ $user->email }}</div>
                        <div class="profile-note">Foto profil berada di bagian atas agar mudah diganti dan tampil lebih rapi.</div>
                    </div>

                    <div class="avatar-upload-row">
                        <label class="form-label mb-1">Foto Profil</label>
                        <input type="file" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                        <div class="form-text">Pilih foto square agar hasil avatar lebih bulat dan rapi.</div>
                        @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

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

                <div class="profile-grid">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-pill btn-primary-soft" type="submit">Simpan Perubahan</button>
                    <a href="{{ route('overview') }}" class="btn-pill btn-outline-soft">Batal</a>
                </div>
            </form>
        </div>

        <div class="profile-side card-surface">
            <div class="info-stack">
                <div class="info-box">
                    <i class="bi bi-lightbulb"></i>
                    <div>
                        <h5>Tips</h5>
                        <p>Gunakan foto yang proporsional dan terang agar avatar tampil bersih di header aplikasi.</p>
                    </div>
                </div>

                <div class="info-box">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <h5>Catatan Akun</h5>
                        <p>Password baru boleh dikosongkan jika Anda hanya ingin mengganti nama, email, atau foto profil.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-panel {
        width: 100%;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.6rem 1rem 0.6rem 1rem;
    }

    .panel-actions {
        display: flex;
        gap: 0.5rem;
    }

    .profile-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(320px, 0.9fr);
        gap: 1.25rem;
        width: 100%;
    }

    .card-surface {
        background: var(--surface);
        border: 1px solid #e6ece6;
        border-radius: 1.25rem;
        box-shadow: 0 10px 28px rgba(24, 59, 40, 0.05);
        min-width: 0;
    }

    .profile-main {
        overflow: hidden;
    }

    .profile-side {
        padding: 1.25rem;
        align-self: stretch;
    }

    .profile-form {
        padding: 1.25rem;
    }

    .profile-hero {
        display: grid;
        grid-template-columns: 128px minmax(0, 1fr);
        gap: 1rem 1.25rem;
        align-items: center;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
        border: 1px solid #e7efe8;
        border-radius: 1.1rem;
        background: linear-gradient(180deg, #fbfefb 0%, #f5faf6 100%);
    }

    .profile-avatar-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .profile-avatar,
    .profile-avatar-fallback {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 12px 26px rgba(24, 59, 40, 0.14);
        background: #eef7ef;
    }

    .profile-avatar-fallback {
        display: grid;
        place-items: center;
        color: var(--primary);
        font-size: 3rem;
    }

    .profile-hero-copy {
        display: grid;
        gap: 0.35rem;
    }

    .profile-name {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text);
        line-height: 1.15;
    }

    .profile-email {
        color: var(--muted);
        font-size: 0.95rem;
        word-break: break-word;
    }

    .profile-note {
        color: var(--muted);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .avatar-upload-row {
        grid-column: 1 / -1;
        display: grid;
        gap: 0.45rem;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .profile-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #edf2ed;
    }

    .form-label {
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .form-control {
        border-radius: 0.75rem;
        border: 1px solid #dfe7df;
        padding: 0.78rem 1rem;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(31, 107, 69, 0.1);
    }

    .form-text {
        color: var(--muted);
        font-size: 0.875rem;
    }

    .info-stack {
        display: grid;
        gap: 1rem;
    }

    .info-box {
        display: flex;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background-color: rgba(31, 107, 69, 0.05);
        border: 1px solid rgba(31, 107, 69, 0.1);
        border-radius: 1rem;
    }

    .info-box i {
        font-size: 1.35rem;
        color: var(--primary);
        flex: 0 0 auto;
        margin-top: 0.15rem;
    }

    .info-box h5 {
        margin: 0 0 0.35rem 0;
        color: var(--text);
        font-weight: 700;
        font-size: 1rem;
    }

    .info-box p {
        margin: 0;
        color: var(--muted);
        font-size: 0.92rem;
        line-height: 1.55;
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
        margin-bottom: 1.25rem;
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
        .profile-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .profile-form,
        .profile-side {
            padding: 1rem;
        }

        .profile-hero {
            grid-template-columns: 1fr;
            text-align: center;
            justify-items: center;
        }

        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-actions {
            flex-direction: column;
        }
    }
</style>
@endsection
