<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Monitoring BPS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #eef5ef;
            --surface: rgba(255, 255, 255, 0.88);
            --surface-strong: #ffffff;
            --primary: #1f6b45;
            --primary-2: #2f8b59;
            --primary-soft: #e3f3ea;
            --text: #173124;
            --muted: #6f8277;
            --border: #dfe9e1;
            --shadow: 0 22px 60px rgba(23, 49, 36, 0.12);
            --shadow-soft: 0 10px 24px rgba(23, 49, 36, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(47, 139, 89, 0.14), transparent 30%),
                radial-gradient(circle at top right, rgba(31, 107, 69, 0.14), transparent 24%),
                radial-gradient(circle at bottom right, rgba(31, 107, 69, 0.08), transparent 24%),
                linear-gradient(135deg, #f4f8f4 0%, #e9f3ea 100%);
        }

        .login-shell {
            height: 100svh;
            display: grid;
            place-items: center;
            padding: 16px;
            position: relative;
            overflow: hidden;
        }

        .login-shell::before,
        .login-shell::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(8px);
            pointer-events: none;
        }

        .login-shell::before {
            width: 320px;
            height: 320px;
            background: rgba(47, 139, 89, 0.08);
            top: -120px;
            left: -120px;
        }

        .login-shell::after {
            width: 260px;
            height: 260px;
            background: rgba(31, 107, 69, 0.08);
            right: -90px;
            bottom: -90px;
        }

        .login-card {
            width: min(1180px, 100%);
            height: min(720px, calc(100svh - 32px));
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: var(--surface);
            border: 1px solid rgba(223, 233, 225, 0.9);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--shadow);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 1;
        }

        .login-hero {
            position: relative;
            padding: 36px;
            color: #eff8f1;
            background:
                linear-gradient(145deg, rgba(21, 75, 47, 0.92) 0%, rgba(22, 89, 54, 0.94) 45%, rgba(31, 107, 69, 0.96) 100%),
                url('{{ asset('asset/bps.png') }}');
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .login-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.14), transparent 22%),
                radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.1), transparent 18%),
                radial-gradient(circle at 70% 80%, rgba(255, 255, 255, 0.08), transparent 16%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 20px;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-mark {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: grid;
            place-items: center;
            box-shadow: var(--shadow-soft);
            flex: 0 0 auto;
        }

        .brand-mark img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .brand-text strong {
            display: block;
            font-size: 1rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-copy {
            max-width: 520px;
            padding: 10px 0 10px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 4rem);
            line-height: 1.03;
            letter-spacing: -0.05em;
            font-weight: 800;
        }

        .hero-copy p {
            margin: 14px 0 0;
            font-size: 0.96rem;
            line-height: 1.58;
            max-width: 48ch;
            color: rgba(240, 248, 242, 0.88);
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.11);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 22px;
            padding: 14px;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(10px);
        }

        .metric-card strong {
            display: block;
            font-size: 1.25rem;
            line-height: 1;
            margin-bottom: 6px;
        }

        .metric-card span {
            font-size: 0.84rem;
            color: rgba(240, 248, 242, 0.82);
        }

        .hero-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        .hero-footer .seal {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .hero-footer .seal i {
            font-size: 1rem;
        }

        .hero-footer .note {
            max-width: 330px;
            font-size: 0.86rem;
            line-height: 1.55;
            color: rgba(240, 248, 242, 0.8);
        }

        .login-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 252, 250, 0.96) 100%);
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .panel-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .panel-head .mini-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: #f4f8f5;
            border: 1px solid #dbe7df;
            font-size: 0.88rem;
            color: var(--muted);
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(23, 49, 36, 0.05);
            transition: all 0.2s ease;
        }

        .panel-head .mini-link:hover {
            color: var(--primary);
            background: #eef7f1;
            border-color: #cfe2d5;
            transform: translateY(-1px);
        }

        .login-title {
            font-size: clamp(1.8rem, 2.2vw, 2.35rem);
            line-height: 1.08;
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .login-subtitle {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.93rem;
            max-width: 44ch;
        }

        .alert-box {
            border-radius: 18px;
            border: 1px solid var(--border);
            padding: 12px 14px;
            margin: 14px 0 0;
            background: #fff;
            box-shadow: var(--shadow-soft);
        }

        .alert-box.alert-danger {
            border-color: #f3d3d8;
            background: #fff8f9;
            color: #8a2033;
        }

        .alert-box.alert-success {
            border-color: #cfe7d7;
            background: #f2fbf6;
            color: #1f6b45;
        }

        .login-form {
            margin-top: 18px;
        }

        .field-group {
            margin-bottom: 12px;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.94rem;
            color: var(--text);
        }

        .field-shell {
            position: relative;
        }

        .field-shell i.prefix {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8da096;
            z-index: 2;
        }

        .field-shell input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #fff;
            padding: 12px 16px 12px 48px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .field-shell input::placeholder {
            color: #9aaa9f;
        }

        .field-shell input:focus {
            border-color: #bcd8c7;
            box-shadow: 0 0 0 4px rgba(31, 107, 69, 0.08);
            transform: translateY(-1px);
        }

        .field-shell input.is-invalid {
            border-color: #d68b98;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.08);
        }

        .field-shell i.toggle-eye {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8da096;
            cursor: pointer;
            z-index: 2;
        }

        .hint-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 14px 0 16px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
            color: var(--muted);
        }

        .remember input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .forgot-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: #f4f8f5;
            border: 1px solid #dbe7df;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            font-size: 0.92rem;
            box-shadow: 0 8px 18px rgba(23, 49, 36, 0.05);
            transition: all 0.2s ease;
        }

        .forgot-link:hover {
            text-decoration: none;
            background: #eef7f1;
            border-color: #cfe2d5;
            transform: translateY(-1px);
        }

        .btn-login {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 13px 18px;
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            box-shadow: 0 18px 28px rgba(31, 107, 69, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 34px rgba(31, 107, 69, 0.24);
            filter: saturate(1.02);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-foot {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.88rem;
        }

        .login-foot strong {
            color: var(--text);
        }

        .form-error {
            display: block;
            color: #b03d52;
            font-size: 0.84rem;
            margin-top: 8px;
        }

        @media (max-width: 1100px) {
            .login-card {
                grid-template-columns: 1fr;
                height: auto;
            }

            .login-hero {
                padding-bottom: 26px;
            }
        }

        @media (max-width: 640px) {
            .login-shell {
                padding: 10px;
                height: 100dvh;
            }

            .login-hero,
            .login-panel {
                padding: 22px 18px;
            }

            .hero-metrics {
                grid-template-columns: 1fr;
            }

            .panel-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .login-foot {
                flex-direction: column;
            }
        }

        @media (max-height: 820px) {
            .login-shell {
                padding: 10px;
            }

            .login-card {
                height: calc(100svh - 20px);
            }

            .login-hero,
            .login-panel {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .hero-copy h1 {
                font-size: clamp(1.9rem, 3.2vw, 3rem);
            }

            .hero-copy p,
            .login-subtitle {
                line-height: 1.45;
            }

            .hero-metrics {
                margin-top: 14px;
            }

            .hero-footer,
            .login-foot {
                margin-top: 10px;
                padding-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <section class="login-hero">
                <div class="hero-inner">
                    <div>
                        <div class="brand-lockup">
                            <div class="brand-mark">
                                <img src="{{ asset('asset/bps.png') }}" alt="Logo BPS">
                            </div>
                            <div class="brand-text">
                                <strong>Badan Pusat Statistik</strong>
                                <strong>Kabupaten Mojokerto</strong>
                            </div>
                        </div>

                        <div class="hero-copy">
                            <div class="eyebrow">
                                <i class="bi bi-shield-lock-fill"></i>
                                Akses Admin
                            </div>
                            <h1>Monitoring target dan realisasi RPD</h1>
                            <p>
                                Masuk untuk mengelola sinkronisasi Google Sheets, upload Excel, pengaturan sumber data,
                                serta arsip monitoring.
                            </p>
                        </div>
                    </div>

                    <div class="hero-footer">
                        <div class="seal">
                            <i class="bi bi-building-fill-check"></i>
                            Sistem Monitoring BPS
                        </div>
                        <div class="note">
                            Gunakan akun admin untuk masuk ke area pengelolaan. Pengguna biasa tetap dapat melihat dashboard publik tanpa login.
                        </div>
                    </div>
                </div>
            </section>

            <section class="login-panel">
                <div class="panel-head">
                    <div class="panel-badge">
                        <i class="bi bi-key-fill"></i>
                        Login Admin
                    </div>
                </div>

                <div>
                    <h2 class="login-title">Masuk ke area admin</h2>
                    <p class="login-subtitle">
                        Silakan gunakan akun admin untuk membuka fitur pengaturan, upload, dan sinkronisasi.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="alert-box alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert-box alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('admin.login.post') }}" method="POST" class="login-form" id="loginForm">
                    @csrf

                    <div class="field-group">
                        <label for="email" class="field-label">Email Admin</label>
                        <div class="field-shell">
                            <i class="bi bi-envelope prefix"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="@error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                placeholder="admin@monitoring.local"
                                required
                                autocomplete="email"
                            >
                        </div>
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field-group">
                        <label for="password" class="field-label">Password</label>
                        <div class="field-shell">
                            <i class="bi bi-lock-fill prefix"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="@error('password') is-invalid @enderror"
                                placeholder="Masukkan password admin"
                                required
                                autocomplete="current-password"
                            >
                            <i class="bi bi-eye-slash toggle-eye" id="togglePassword" title="Tampilkan password"></i>
                        </div>
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="hint-row">
                        <label class="remember" for="remember">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            Ingat saya
                        </label>

                        <a href="{{ route('overview') }}" class="forgot-link">Masuk Tanpa Login</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Admin
                    </button>
                </form>

                
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    togglePassword.className = isHidden ? 'bi bi-eye toggle-eye' : 'bi bi-eye-slash toggle-eye';
                    togglePassword.title = isHidden ? 'Sembunyikan password' : 'Tampilkan password';
                });
            }
        })();
    </script>
</body>
</html>
