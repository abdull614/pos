@props(['title' => 'TokoPos Admin', 'pageTitle' => '', 'hideSidebar' => false, 'fullHeight' => false, 'noScroll' => false])
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TokoPos Admin' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* Jangan set overflow: hidden pada html/body — biarkan halaman bisa discroll */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fafafa;
            transition: padding-left 0.3s ease;
        }

        /* Desktop: sidebar fixed */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 224px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            z-index: 50;
            transform: translateX(0);
            transition: transform 0.25s ease;
        }

        /* Navbar fixed, menyesuaikan left saat sidebar terbuka/tertutup */
        #navbar {
            position: fixed;
            top: 0;
            left: 224px;
            right: 0;
            height: 3.5rem;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            z-index: 40;
            transition: left 0.25s ease;
        }

        /* Ketika sidebar ditutup, geser navbar dan body padding */
        body.sidebar-closed #sidebar { transform: translateX(-100%); }
        body.sidebar-closed #navbar { left: 0; }

        /* Konten utama: beri margin-top sesuai navbar dan padding responsif.
           Gunakan min-height agar footer/area bawah tidak menempel */
        main.app-content {
            margin-top: 3.5rem;
            padding: 0.75rem;
            min-height: calc(100vh - 3.5rem);
            transition: margin-left 0.25s ease;
        }
        @media (min-width: 640px) {
            main.app-content { padding: 1.25rem; }
        }
        @media (min-width: 1024px) {
            main.app-content { padding: 2rem; }
        }

        /* fixed-height (default): kunci tinggi main = viewport, tapi tetap
           bisa scroll KALAU kontennya lebih panjang dari layar.
           Cocok untuk halaman yang isinya bisa panjang (form, detail, dll). */
        main.app-content.fixed-height {
            height: calc(100vh - 3.5rem);
            min-height: 0;
            overflow: hidden auto;
        }

        /* no-scroll: benar-benar TIDAK PERNAH menampilkan scrollbar,
           berapa pun tinggi kontennya. Kalau konten lebih tinggi dari
           layar, bagian bawah akan terpotong (bukan bisa discroll).
           Pakai ini HANYA untuk halaman yang kontennya dipastikan
           selalu muat, misal karena sudah dipaginasi (mis. daftar produk). */
        main.app-content.no-scroll {
            height: calc(100vh - 3.5rem);
            min-height: 0;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Kunci body/html juga, supaya scrollbar halaman (di pinggir kanan
           browser) benar-benar hilang, bukan cuma scrollbar di dalam main.
           Tanpa ini, body masih bisa overflow walau main sudah dikunci,
           kalau total tinggi main (margin+border+padding) sedikit melebihi
           100vh akibat pembulatan/box-model. */
        html.page-no-scroll,
        body.page-no-scroll {
            height: 100%;
            overflow: hidden;
        }

        /* Desktop: beri ruang untuk sidebar */
        body:not(.sidebar-closed) main.app-content { margin-left: 224px; }
        body.sidebar-closed main.app-content { margin-left: 0; }

        /* Mobile behavior: sidebar overlay, navbar full width */
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                left: 0;
                top: 0;
                transform: translateX(-100%);
                width: 16rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            }
            body:not(.sidebar-closed) #sidebar { transform: translateX(0); }
            #navbar { left: 0 !important; }
            main.app-content { margin-left: 0 !important; }
            #sidebarOverlay { display: none; } /* default hidden; toggled by JS */
            #sidebarOverlay.active { display: block; }
        }

        /* Safety: limit images inside cards so they don't break layout */
        .receipt-img { max-height: 14rem; width: 100%; object-fit: contain; display:block; }

        /* Make sure scrollbars appear inside main, not on body when sidebar open on mobile */
        @media (max-width: 1023px) {
            body.sidebar-closed { overflow: auto; }
        }
    </style>

    {{ $styles ?? '' }}
</head>

<body class="{{ $hideSidebar ? 'sidebar-closed' : '' }} {{ $noScroll ? 'page-no-scroll' : '' }}">
    @unless($hideSidebar)
        {{-- Sidebar (partial) --}}
        @includeIf('partials.sidebar')

        {{-- Navbar (partial) --}}
        @includeIf('partials.nav-bar')
    @endunless

    {{-- Main content area; gunakan class app-content agar margin/top terkelola --}}
    <main class="app-content {{ $hideSidebar ? 'flex items-center justify-center' : '' }} {{ $noScroll ? 'no-scroll' : ($fullHeight ? 'fixed-height' : '') }}">
        <div class="w-full max-w-400 mx-auto h-full flex flex-col">

            {{ $slot }}
        </div>
    </main>

    {{-- Overlay untuk mobile sidebar --}}
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 hidden" onclick="closeSidebar()"></div>

    {{ $scripts ?? '' }}
    @stack('scripts')

    <script>
        @if($noScroll)
        document.documentElement.classList.add('page-no-scroll');
        @endif

        const overlay = document.getElementById('sidebarOverlay');

        const isMobile = () => window.innerWidth < 1024;

        function openSidebar() {
            document.body.classList.remove('sidebar-closed');
            if (isMobile()) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            localStorage.setItem('sidebarOpen', 'true');
        }

        function closeSidebar() {
            document.body.classList.add('sidebar-closed');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            localStorage.setItem('sidebarOpen', 'false');
        }

        function toggleSidebar() {
            if (document.body.classList.contains('sidebar-closed')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        }

        // Inisialisasi berdasarkan preferensi user
        const saved = localStorage.getItem('sidebarOpen');
        if (saved === 'false') {
            document.body.classList.add('sidebar-closed');
        } else {
            document.body.classList.remove('sidebar-closed');
        }

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            } else {
                // jika mobile dan sidebar terbuka, pastikan overlay aktif
                if (!document.body.classList.contains('sidebar-closed')) {
                    overlay.classList.add('active');
                }
            }
        });

        // expose untuk partials (mis. tombol toggle di navbar)
        window.openSidebar = openSidebar;
        window.closeSidebar = closeSidebar;
        window.toggleSidebar = toggleSidebar;
    </script>
</body>
</html>