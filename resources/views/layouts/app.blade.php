<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --app-bg: #f4f7f3;
            --surface: #ffffff;
            --surface-soft: #f8fbf8;
            --sidebar-border: #e6ece6;
            --primary: #1f6b45;
            --primary-2: #2f8b59;
            --primary-soft: #e3f3ea;
            --accent: #d9efe1;
            --text: #193126;
            --muted: #73817a;
            --shadow: 0 18px 50px rgba(24, 59, 40, 0.08);
            --shadow-soft: 0 10px 28px rgba(24, 59, 40, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(47, 139, 89, 0.08), transparent 32%),
                radial-gradient(circle at top right, rgba(31, 107, 69, 0.07), transparent 26%),
                var(--app-bg);
            color: var(--text);
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 255px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(18px);
            border-right: 1px solid var(--sidebar-border);
            box-shadow: 10px 0 28px rgba(26, 49, 34, 0.05);
            padding: 22px 18px 18px;
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 6px 10px 20px;
            margin-bottom: 14px;
            border-bottom: 1px solid #edf2ed;
        }

        .brand img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .brand-copy strong {
            display: block;
            font-size: 15px;
            line-height: 1.2;
        }

        .brand-copy span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .sidebar-label {
            margin: 18px 10px 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #99a7a0;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            color: var(--text);
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .nav-link .icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: #f2f7f2;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: #7a887f;
        }

        .nav-link .icon img {
            width: 18px;
            height: 18px;
            object-fit: contain;
        }

        .nav-link .text {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-link .badge-pill {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #edf4ee;
            color: #6a7a71;
        }

        .nav-link:hover {
            background: #f6faf6;
            border-color: #ebf1eb;
            transform: translateX(2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-soft), #f8fcfa);
            border-color: #cfe7d7;
            box-shadow: 0 12px 24px rgba(31, 107, 69, 0.08);
        }

        .nav-link.active .icon {
            background: var(--primary);
            color: white;
        }

        .sidebar-footer {
            margin-top: 18px;
            padding-top: 18px;
        }

        .sidebar-card {
            background: linear-gradient(180deg, #1f6b45 0%, #184f35 100%);
            border-radius: 22px;
            padding: 16px;
            color: #f4fbf6;
            box-shadow: 0 18px 28px rgba(31, 107, 69, 0.18);
        }

        .sidebar-card small {
            display: block;
            color: rgba(244, 251, 246, 0.78);
            margin-top: 6px;
            line-height: 1.45;
        }

        .sidebar-card .mini-stat {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: rgba(244, 251, 246, 0.84);
        }

        .sidebar-card .mini-stat strong {
            font-size: 18px;
            color: #fff;
        }

        .main-content {
            margin-left: 255px;
            width: calc(100% - 255px);
            padding: 22px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 18px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(230, 236, 230, 0.95);
            box-shadow: var(--shadow-soft);
        }

        .search-box {
            flex: 1;
            max-width: 420px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b9891;
        }

        .search-box input {
            width: 100%;
            border: none;
            outline: none;
            border-radius: 999px;
            background: #f3f7f3;
            padding: 12px 52px 12px 44px;
            font-size: 14px;
            color: var(--text);
        }

        .search-box input::placeholder {
            color: #9aa7a1;
        }

        .search-shortcut {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 1px solid #e4ebe4;
            background: white;
            border-radius: 10px;
            padding: 4px 8px;
            font-size: 12px;
            color: #7d8983;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon-btn {
            width: 42px;
            height: 42px;
            border: 1px solid #e6ece6;
            border-radius: 50%;
            background: white;
            color: #52635b;
            display: grid;
            place-items: center;
            box-shadow: 0 8px 18px rgba(20, 35, 24, 0.05);
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .theme-toggle i {
            font-size: 16px;
        }

        .profile-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 10px;
            position: relative;
            cursor: pointer;
        }

        .profile-chip .avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #efc7b7, #dca28d);
            flex: 0 0 auto;
        }

        .profile-chip .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-copy strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }

        .profile-copy span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: -10px;
            background: white;
            border: 1px solid #e6ece6;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(20, 35, 24, 0.12);
            min-width: 220px;
            margin-top: 8px;
            z-index: 1000;
            display: none;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #edf2ed;
        }

        .profile-dropdown-item:last-child {
            border-bottom: none;
        }

        .profile-dropdown-item:hover {
            background: #f6faf6;
        }

        .profile-dropdown-label {
            font-size: 12px;
            color: var(--muted);
            padding: 10px 16px 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .profile-dropdown-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            background: #e3f3ea;
            color: var(--primary);
        }


        .page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin: 22px 4px 18px;
        }

        .page-title {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 6px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 14px;
            max-width: 560px;
        }

        .page-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-pill {
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-pill:hover {
            transform: translateY(-1px);
        }

        .btn-primary-soft {
            background: linear-gradient(135deg, #1f6b45, #2f8b59);
            color: white;
            box-shadow: 0 16px 24px rgba(31, 107, 69, 0.18);
        }

        .btn-outline-soft {
            background: white;
            color: var(--text);
            border-color: #d7e2d9;
        }

        .dashboard-grid {
            display: grid;
            gap: 18px;
        }

        .stats-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .stat-card {
            min-height: 128px;
            background: white;
            border: 1px solid #edf2ed;
            border-radius: 24px;
            padding: 18px;
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card--primary {
            background: linear-gradient(180deg, #236e47 0%, #185534 100%);
            border-color: transparent;
            color: white;
        }

        .stat-card .label {
            font-size: 14px;
            color: inherit;
            opacity: 0.9;
        }

        .stat-card .value {
            font-size: 42px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.06em;
        }

        .stat-card .trend {
            font-size: 12px;
            color: inherit;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: 2fr 1fr;
        }

        .panel {
            background: white;
            border: 1px solid #edf2ed;
            border-radius: 24px;
            box-shadow: var(--shadow-soft);
            padding: 18px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .panel-title {
            font-size: 18px;
            font-weight: 800;
        }

        .panel-small {
            font-size: 13px;
            color: var(--muted);
        }

        .chart-bars {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            align-items: end;
            gap: 14px;
            height: 190px;
            margin-top: 20px;
        }

        .bar-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .bar {
            width: 100%;
            border-radius: 18px;
            min-height: 42px;
            background: repeating-linear-gradient(
                135deg,
                #dbe4dd 0,
                #dbe4dd 8px,
                #eef3ef 8px,
                #eef3ef 16px
            );
        }

        .bar.filled {
            background: linear-gradient(180deg, #50b37b 0%, #1f6b45 100%);
            box-shadow: 0 10px 18px rgba(31, 107, 69, 0.22);
        }

        .day {
            font-size: 12px;
            color: #8a968f;
        }

        .reminder-card {
            background: linear-gradient(180deg, #f8fcf8 0%, #eef7ef 100%);
            border: 1px solid #d8e8da;
            border-radius: 24px;
            padding: 18px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .reminder-card h4 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .reminder-card p {
            color: var(--muted);
            margin-bottom: 0;
        }

        .project-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .project-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .project-dot {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: white;
            flex: 0 0 auto;
        }

        .project-text strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }

        .project-text span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .table-card .table {
            margin-bottom: 0;
        }

        .table-card .table thead th {
            border-top: none;
            color: #68756f;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f8fbf8;
        }

        .table-card .table td,
        .table-card .table th {
            vertical-align: middle;
            padding-top: 14px;
            padding-bottom: 14px;
        }

        html[data-theme='dark'] {
            color-scheme: dark;
        }

        html[data-theme='dark'] body {
            --app-bg: #0f1713;
            --surface: #131d18;
            --surface-soft: #18241e;
            --sidebar-border: #213229;
            --primary: #46b67a;
            --primary-2: #5fd18f;
            --primary-soft: #1a2f24;
            --accent: #1c2d24;
            --text: #deebe4;
            --muted: #8ea499;
            --shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            --shadow-soft: 0 10px 28px rgba(0, 0, 0, 0.3);
            background:
                radial-gradient(circle at top left, rgba(70, 182, 122, 0.13), transparent 28%),
                radial-gradient(circle at top right, rgba(95, 209, 143, 0.1), transparent 26%),
                var(--app-bg);
        }

        html[data-theme='dark'] .sidebar,
        html[data-theme='dark'] .topbar,
        html[data-theme='dark'] .panel,
        html[data-theme='dark'] .stat-card,
        html[data-theme='dark'] .search-shortcut,
        html[data-theme='dark'] .icon-btn,
        html[data-theme='dark'] .btn-outline-soft,
        html[data-theme='dark'] .search-box input,
        html[data-theme='dark'] .nav-link .icon {
            background: var(--surface);
            color: var(--text);
            border-color: var(--sidebar-border);
        }

        html[data-theme='dark'] .sidebar {
            background: rgba(18, 27, 22, 0.92);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
        }

        html[data-theme='dark'] .brand {
            border-bottom-color: #25372d;
        }

        html[data-theme='dark'] .sidebar-label {
            color: #7b9287;
        }

        html[data-theme='dark'] .nav-link {
            color: var(--text);
        }

        html[data-theme='dark'] .nav-link:hover {
            background: #1c2a23;
            border-color: #2b3d33;
        }

        html[data-theme='dark'] .nav-link.active {
            background: linear-gradient(135deg, #1d3528, #1a2d23);
            border-color: #2f4f3f;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);
        }

        html[data-theme='dark'] .nav-link.active .icon {
            background: #2a8b5a;
            border-color: #2a8b5a;
            color: #f2fff8;
        }

        html[data-theme='dark'] .nav-link .badge-pill {
            background: #22352b;
            color: #9ab1a5;
        }

        html[data-theme='dark'] .search-box input {
            background: #1a2721;
        }

        html[data-theme='dark'] .search-box input::placeholder,
        html[data-theme='dark'] .search-shortcut,
        html[data-theme='dark'] .search-box i,
        html[data-theme='dark'] .day,
        html[data-theme='dark'] .table-card .table thead th {
            color: #8ea498;
        }

        html[data-theme='dark'] .search-shortcut {
            background: #17231d;
            border-color: #2a3d32;
        }

        html[data-theme='dark'] .icon-btn:hover,
        html[data-theme='dark'] .btn-outline-soft:hover {
            background: #1c2a23;
        }

        html[data-theme='dark'] .btn-outline-soft {
            border-color: #2a3d32;
            color: var(--text);
        }

        html[data-theme='dark'] .stat-card {
            border-color: #24362c;
        }

        html[data-theme='dark'] .stat-card:not(.stat-card--primary) {
            background: #131f19;
        }

        html[data-theme='dark'] .stat-card--primary,
        html[data-theme='dark'] .sidebar-card,
        html[data-theme='dark'] .btn-primary-soft {
            background: linear-gradient(180deg, #2b8f5d 0%, #1a5e3b 100%);
        }

        html[data-theme='dark'] .panel,
        html[data-theme='dark'] .topbar {
            border-color: #24362c;
        }

        html[data-theme='dark'] .bar {
            background: repeating-linear-gradient(
                135deg,
                #2a3a31 0,
                #2a3a31 8px,
                #1f2e26 8px,
                #1f2e26 16px
            );
        }

        html[data-theme='dark'] .bar.filled {
            background: linear-gradient(180deg, #5ac086 0%, #2a8b5a 100%);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.3);
        }

        html[data-theme='dark'] .reminder-card {
            background: linear-gradient(180deg, #16251e 0%, #12201a 100%);
            border-color: #294237;
        }

        html[data-theme='dark'] .table-card .table {
            color: #d5e3db;
        }

        html[data-theme='dark'] .table-card .table thead th {
            background: #16241d;
            border-bottom-color: #2a3d33;
        }

        html[data-theme='dark'] .table-card .table td {
            border-bottom-color: #223229;
        }

        html[data-theme='dark'] .badge.text-bg-light {
            background: #22352b !important;
            color: #d4e2da !important;
            border-color: #32473b !important;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991px) {
            .app-shell {
                display: block;
            }

            .sidebar {
                position: relative;
                inset: auto;
                width: 100%;
                box-shadow: none;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            .topbar,
            .page-head {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .topbar-actions {
                flex-wrap: wrap;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <img src="{{ asset('asset/bps.png') }}" alt="BPS Logo">
                <div class="brand-copy">
                    <strong>Monitoring</strong>
                    <span>BPS Dashboard</span>
                </div>
            </div>

            <div class="sidebar-label">Menu</div>
            <ul class="nav-menu">
                <li>
                    <a href="{{ route('overview') }}" class="nav-link @if(Route::currentRouteName() == 'overview' || Route::currentRouteName() == 'overview.alt') active @endif">
                        <span class="icon"><img src="{{ asset('asset/overview.png') }}" alt="Overview"></span>
                        <span class="text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('keutata') }}" class="nav-link @if(Route::currentRouteName() == 'keutata') active @endif">
                        <span class="icon"><img src="{{ asset('asset/keuangan.png') }}" alt="Keutata"></span>
                        <span class="text">Keutata</span>
                        <span class="badge-pill">Data</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('archive') }}" class="nav-link @if(Route::currentRouteName() == 'archive') active @endif">
                        <span class="icon"><i class="bi bi-archive"></i></span>
                        <span class="text">Arsip File</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-label">General</div>
            <ul class="nav-menu">
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <li>
                        <a href="{{ route('admin.settings.index') }}" class="nav-link @if(Route::currentRouteName() == 'admin.settings.index') active @endif">
                            <span class="icon"><i class="bi bi-gear"></i></span>
                            <span class="text">Settings</span>
                        </a>
                    </li>
                @endif
                <li>
                    <a href="#" class="nav-link">
                        <span class="icon"><i class="bi bi-question-circle"></i></span>
                        <span class="text">Help</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="sidebar-card">
                    <strong>Monitoring aktif</strong>
                    <small>Kelola data, pantau status, dan baca file Excel dari satu dashboard.</small>
                    <div class="mini-stat">
                        <span>System health</span>
                        <strong>98%</strong>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" value="" placeholder="Search task">
                    <span class="search-shortcut">Ctrl F</span>
                </div>

                <div class="topbar-actions">
                    <button type="button" id="theme-toggle" class="icon-btn theme-toggle" aria-label="Switch theme" title="Switch theme">
                        <i id="theme-toggle-icon" class="bi bi-moon-stars-fill"></i>
                    </button>
                    <button type="button" class="icon-btn" aria-label="Messages"><i class="bi bi-envelope"></i></button>
                    <button type="button" class="icon-btn" aria-label="Notifications"><i class="bi bi-bell"></i></button>
                    @if(auth()->check() && auth()->user()->role === 'admin')
                        <div class="profile-chip">
                            <div class="avatar">
                                @if(!empty(auth()->user()->avatar))
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name ?? 'Admin' }}">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=efc7b7&color=2b2b2b" alt="{{ auth()->user()->name ?? 'Admin' }}">
                                @endif
                            </div>
                            <div class="profile-copy">
                                    <strong>{{ auth()->user()->name ?? 'Admin' }}</strong>
                                    <span>{{ auth()->user()->email ?? '' }}</span>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <a href="{{ route('profile.edit') }}" class="btn-pill btn-outline-soft" style="font-size:0.9rem; padding:8px 12px;">Edit Profil</a>
                                <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn-pill btn-outline-soft border-0">Logout</button>
                                </form>
                            </div>
                    @else
                        <a href="{{ route('admin.login') }}" class="btn-pill btn-primary-soft border-0">Login Admin</a>
                    @endif
                </div>
            </header>

            <section class="page-head">
                <div>
                    <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                    <p class="page-subtitle">@yield('subtitle', '')</p>
                </div>
                <div class="page-actions">
                    @yield('page-actions')
                </div>
            </section>

            <div class="dashboard-grid">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const key = 'monitoring-theme';
            const root = document.documentElement;
            const button = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-toggle-icon');

            function applyTheme(theme) {
                root.setAttribute('data-theme', theme);
                if (icon) {
                    icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                }

                if (button) {
                    const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
                    button.setAttribute('aria-label', label);
                    button.setAttribute('title', label);
                }
            }

            function resolveTheme() {
                const saved = window.localStorage.getItem(key);
                if (saved === 'dark' || saved === 'light') {
                    return saved;
                }

                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            let currentTheme = resolveTheme();
            applyTheme(currentTheme);

            if (button) {
                button.addEventListener('click', function () {
                    currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    window.localStorage.setItem(key, currentTheme);
                    applyTheme(currentTheme);
                });
            }
        })();

        // Auto-dismiss alerts after 10 seconds
        (function () {
            const alerts = document.querySelectorAll('.alert[role="alert"]');
            alerts.forEach(function (alert) {
                // Only auto-dismiss success alerts (typically "File Excel dimuat" notifications)
                if (alert.classList.contains('alert-success')) {
                    const timeout = setTimeout(function () {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 10000); // 10 seconds

                    // Clear timeout if user manually closes the alert
                    alert.addEventListener('close.bs.alert', function () {
                        clearTimeout(timeout);
                    });
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
