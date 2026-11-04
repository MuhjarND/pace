<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang layanan') · {{ config('ui.brand.full_name') }}</title>
    <meta name="description" content="Sistem Pelayanan Terpadu Satu Pintu Online - Layanan publik digital yang cepat dan efisien">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    @yield('styles')
    <link rel="stylesheet" href="{{ asset('css/product-ui.css') }}?v={{ filemtime(public_path('css/product-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/illustrated-portal.css') }}?v={{ filemtime(public_path('css/illustrated-portal.css')) }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/'.config('ui.brand.favicon')) }}">
<link rel="apple-touch-icon" href="{{ asset('images/'.config('ui.brand.touch_icon')) }}">
<link rel="stylesheet" href="{{ asset('css/pace-brand.css') }}?v={{ filemtime(public_path('css/pace-brand.css')) }}"></head>
<body class="{{ request()->routeIs('landing') ? 'public-landing' : (request()->routeIs('guest.*') ? 'public-access' : 'workspace-page') }} {{ request()->routeIs('password.*','verification.*') ? 'auth-recovery' : '' }}">
    <a class="skip-link" href="#mainContent">Lewati ke konten</a>
    <button class="sidebar-scrim" id="sidebarScrim" aria-label="Tutup menu navigasi" hidden></button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="pace-brand-home" href="{{ route('dashboard') }}">@include('partials.brand',['brandClass'=>'pace-sidebar-brand'])</a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu navigasi">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <p class="nav-group-label">Ruang layanan</p>
            <a href="{{ route('landing') }}" class="nav-item"><div class="nav-icon"><i class="fas fa-landmark" aria-hidden="true"></i></div><span>Portal masyarakat</span></a>
            <a href="{{ route('public.cases') }}" class="nav-item"><div class="nav-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></div><span>Cek status perkara dan akta cerai</span></a>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-th-large" aria-hidden="true"></i></div>
                <span>Dashboard</span>
                <div class="nav-indicator"></div>
            </a>
            <a href="{{ route('services.index') }}" class="nav-item {{ request()->routeIs('services.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-concierge-bell" aria-hidden="true"></i></div>
                <span>Layanan</span>
            </a>
            @auth
            <p class="nav-group-label nav-group-channel">Kanal konsultasi</p>
            <a href="{{ route('chat.index') }}" class="nav-item {{ request()->routeIs('chat.*') && !request()->routeIs('chat.voice') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-comments" aria-hidden="true"></i></div>
                <span>Chat PTSP</span>
                <div class="nav-badge" id="chatBadge" style="display:none;">0</div>
            </a>
            <a href="{{ route('chat.voice') }}" class="nav-item {{ request()->routeIs('chat.voice') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-microphone-lines" aria-hidden="true"></i></div>
                <span>Voice to Chat</span>
            </a>
            <a href="{{ route('meetings.index') }}" class="nav-item {{ request()->routeIs('meetings.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-video" aria-hidden="true"></i></div>
                <span>Google Meet</span>
            </a>
            <a href="{{ route('call-center.index') }}" class="nav-item {{ request()->routeIs('call-center.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-headset" aria-hidden="true"></i></div>
                <span>Call Center</span>
            </a>
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            @if(auth()->user()->canHandleComplaints())
            <p class="nav-group-label nav-group-channel">Pengawasan PTA</p>
            <a href="{{ route('complaints.index') }}" class="nav-item {{ request()->routeIs('complaints.*') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></div><span>Pengaduan PTA</span></a>
            @endif
            <p class="nav-group-label nav-group-channel">Operasional PTSP</p>
            <a href="{{ route('operations.public-data') }}" class="nav-item {{ request()->routeIs('operations.public-data') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-map-location-dot" aria-hidden="true"></i></div><span>Wilayah dan biaya</span></a>
            <a href="{{ route('operations.queue') }}" class="nav-item {{ request()->routeIs('operations.queue*') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-users-line" aria-hidden="true"></i></div><span>Antrean pelayanan</span></a>
            <a href="{{ route('operations.hours') }}" class="nav-item {{ request()->routeIs('operations.hours') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-clock" aria-hidden="true"></i></div><span>Jam layanan</span></a>
            @endif
            @if(auth()->user()->canManageMasterData())
            <p class="nav-group-label nav-group-channel">Administrasi</p>
            <a href="{{ route('admin.accounts.index') }}" class="nav-item {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-user-gear" aria-hidden="true"></i></div><span>Akun pengguna</span></a>
            <a href="{{ route('admin.work-units.index') }}" class="nav-item {{ request()->routeIs('admin.work-units.*') ? 'active' : '' }}"><div class="nav-icon"><i class="fas fa-building-columns" aria-hidden="true"></i></div><span>Satuan kerja</span></a>
            @endif
            @endauth
        </nav>

        <div class="sidebar-footer">
            @auth
            <div class="user-card">
                <div class="user-avatar">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="user-info">
                    <h4>{{ auth()->user()->name ?? 'User' }}</h4>
                    <span style="font-size: 11px;">{{ auth()->user()->role === 'user' ? 'Pengunjung' : ucfirst(auth()->user()->role) }}</span>
                </div>
            </div>

            @if(auth()->user()->role === 'user')
            <a href="{{ route('staff.login') }}" class="btn btn-outline-light btn-sm" style="width: 100%; margin-top: 10px;">
                <i class="fas fa-user-shield" aria-hidden="true"></i> Login Petugas
            </a>
            @else
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Keluar</span>
                </button>
            </form>
            @endif
            @endauth
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent" tabindex="-1">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <div class="breadcrumb">
                    <span class="breadcrumb-item">{{ config('ui.brand.name') }}</span>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    <span class="breadcrumb-item active" aria-current="page">@yield('page-title', 'Dashboard')</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="search-box">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="text" placeholder="Cari menu..." id="globalSearch" aria-label="Cari menu navigasi">
                    <button class="voice-search-btn" id="globalVoiceSearch" title="Cari menu dengan suara" aria-label="Cari menu dengan suara">
                        <i class="fas fa-microphone" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="topbar-actions">
                    <div class="dropdown" style="position: relative;">
                        <button class="action-btn" id="notifBtn" onclick="toggleNotifDropdown()" aria-label="Buka notifikasi" aria-expanded="false" aria-controls="notifDropdown">
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <span class="action-badge" id="topbarBadge" style="display: none;">0</span>
                        </button>
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-header">
                                <h6>Notifikasi</h6>
                                <span class="badge badge-primary" id="notifCountHeader" style="display: none;">0 Baru</span>
                            </div>
                            <div id="notifFeedback" class="notif-feedback" hidden><p id="notifError" role="status"></p><button id="notifRetry" type="button" class="btn btn-secondary btn-sm">Coba lagi</button></div>
                            <div class="notif-body" id="notifList">
                                <div class="notif-empty-state">
                                    <i class="far fa-bell-slash" aria-hidden="true"></i><p>Belum ada notifikasi baru.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <span class="action-btn" style="cursor:default;" title="Tema terang" role="img" aria-label="Tema terang">
                        <i class="fas fa-sun" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content animate__animated animate__fadeIn">
            @if(session('success'))
                <div class="alert alert-success" role="status">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    {{ session('success') }}
                    <button class="alert-close" aria-label="Tutup pemberitahuan" onclick="this.parentElement.remove()"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    {{ session('error') }}
                    <button class="alert-close" aria-label="Tutup pemberitahuan" onclick="this.parentElement.remove()"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
            @endif

            @yield('content')
            @if(request()->routeIs('password.*','verification.*'))
            <p class="auth-recovery-nav"><a href="{{ route('landing') }}">Kembali ke beranda <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
            @endif
        </div>
    </main>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- App JS -->
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
    <script src="{{ asset('js/voice.js') }}?v={{ filemtime(public_path('js/voice.js')) }}"></script>

    @yield('scripts')

    <script>
    function toggleNotifDropdown() {
        const dd = document.getElementById('notifDropdown');
        if (!dd) return;
        dd.classList.toggle('show');
        document.getElementById('notifBtn').setAttribute('aria-expanded', dd.classList.contains('show'));
    }
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        if (!notifBtn || !notifDropdown) return;

        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('show');
            notifBtn.setAttribute('aria-expanded', 'false');
        }
    });
    </script>

    @auth
    @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
    <script>
    let notificationsBusy = false;
    let notificationsStopped = false;
    function fetchNotifications() {
        if (document.hidden || notificationsBusy || notificationsStopped) return;
        notificationsBusy = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 12000);
        const safeText = value => String(value == null ? '' : value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));
        fetch('{{ route("notifications.fetch") }}', { signal: controller.signal, cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => {
            if (response.status === 401 || response.status === 403) notificationsStopped = true;
            if (!response.ok || response.redirected) throw new Error('Notifikasi belum dapat dimuat.');
            return response.json();
        })
        .then(data => {
            if(data.success) {
                document.getElementById('notifFeedback').hidden = true;
                // Update Sidebar Chat Badge
                const chatBadge = document.getElementById('chatBadge');
                if(chatBadge) {
                    if(data.unread_chats > 0) {
                        chatBadge.style.display = 'flex';
                        chatBadge.textContent = data.unread_chats;
                    } else {
                        chatBadge.style.display = 'none';
                    }
                }

                // Update Topbar Badge
                const topbarBadge = document.getElementById('topbarBadge');
                const notifCountHeader = document.getElementById('notifCountHeader');
                if(topbarBadge) {
                    if(data.total_count > 0) {
                        topbarBadge.style.display = 'flex';
                        topbarBadge.textContent = data.total_count;
                        
                        // Update Dropdown Header
                        if (notifCountHeader) {
                            notifCountHeader.style.display = 'inline-flex';
                            notifCountHeader.textContent = data.total_count + ' Baru';
                        }
                    } else {
                        topbarBadge.style.display = 'none';
                        if (notifCountHeader) {
                            notifCountHeader.style.display = 'none';
                            notifCountHeader.textContent = '0 Baru';
                        }
                    }
                }

                // Update Dropdown List
                const list = document.getElementById('notifList');
                if(data.notifications.length > 0) {
                    let html = '';
                    data.notifications.forEach(notif => {
                        let icon = notif.type === 'chat' ? '<i class="fas fa-comment-dots" style="color: var(--info);" aria-hidden="true"></i>' : '<i class="fas fa-video" style="color: var(--warning);" aria-hidden="true"></i>';
                        html += `
                        <a href="${safeText(notif.url)}" class="notif-item">
                            <div class="notif-icon-box">
                                ${icon}
                            </div>
                            <div class="notif-content">
                                <p class="notif-title">${safeText(notif.title)}</p>
                                <p class="notif-msg">${safeText(notif.message)}</p>
                                <span class="notif-time">${safeText(notif.time)}</span>
                            </div>
                        </a>
                        `;
                    });
                    list.innerHTML = html;
                } else {
                    list.innerHTML = `
                        <div class="notif-empty-state">
                            <i class="far fa-bell-slash" aria-hidden="true"></i><p>Belum ada notifikasi baru.</p>
                        </div>
                    `;
                }
            }
        }).catch(() => {
            document.getElementById('notifFeedback').hidden = false;
            document.getElementById('notifError').textContent = notificationsStopped ? 'Sesi atau akses berubah. Muat ulang halaman untuk masuk kembali.' : 'Notifikasi belum dapat diperbarui. Data terakhir tetap ditampilkan.';
            document.getElementById('notifRetry').textContent = notificationsStopped ? 'Muat ulang halaman' : 'Coba lagi';
        }).finally(() => { clearTimeout(timeout); notificationsBusy = false; });
    }

    document.getElementById('notifRetry').addEventListener('click', () => { if (notificationsStopped) location.reload(); else fetchNotifications(); });

    // Poll every 5 seconds
    setInterval(fetchNotifications, 5000);
    fetchNotifications(); // Initial call
    </script>
    @endif
    @endauth

    <style>
        .notif-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: var(--surface);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-shadow: var(--glass-shadow);
            margin-top: 10px;
            display: none;
            overflow: hidden;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .notif-dropdown.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .notif-header {
            padding: 15px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-header h6 { margin: 0; font-weight: 600; color: var(--text); }
        .notif-body {
            max-height: 300px;
            overflow-y: auto;
        }
        .notif-item {
            display: flex;
            align-items: start;
            padding: 12px 15px;
            border-bottom: 1px solid var(--glass-border);
            color: var(--text);
            text-decoration: none;
            transition: background 0.2s;
        }
        .notif-item:hover {
            background: var(--surface-muted);
            text-decoration: none;
            color: var(--text);
        }
        .notif-icon-box {
            width: 36px;
            height: 36px;
            background: var(--surface-muted);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .notif-content { flex: 1; }
        .notif-title { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
        .notif-msg { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; line-height: 1.3; }
        .notif-time { font-size: 10px; color: var(--text-muted); display: block; text-align: right; }
        .notif-empty-state {
            min-height: 112px;
            flex-direction: column;
            gap: 12px;
            font-size: 12px;
            color: var(--text-muted);
            padding: 20px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notif-empty-state i {
            font-size: 26px;
            color: var(--text-muted);
        }
    </style>
</body>
</html>
