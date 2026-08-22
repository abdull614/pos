@props(['pageTitle' => 'Dashboard'])

<nav id="navbar"
     class="fixed top-0 left-56 right-0 h-14 bg-white border-b border-gray-200 px-4 sm:px-6 flex items-center justify-between z-50 shadow-sm transition-all duration-300">
    <div class="flex items-center gap-3">
        <button type="button" onclick="toggleSidebar()"
                class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition">
            <i class="bi bi-list text-xl"></i>
        </button>

        <h2 class="text-gray-900 font-bold text-base">{{ $pageTitle }}</h2>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">
        <div class="relative hidden md:block">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" placeholder="Cari transaksi..."
                   class="pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent w-52">
        </div>

        <button type="button" class="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition">
            <i class="bi bi-bell text-base"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

        @php
            $user = auth('web')->user();
        @endphp

        <div id="profileWrapper" class="relative pl-2 sm:pl-3 border-l border-gray-200 z-50">
            <button type="button" onclick="toggleProfileMenu()" class="flex items-center gap-2.5 focus:outline-none cursor-pointer">
                <div class="text-right hidden sm:block">
                    <p class="text-gray-900 font-bold text-sm leading-tight">
                        {{ $user->name ?? 'Admin' }}
                    </p>
                    <p class="text-xs text-gray-400">Pemilik Toko</p>
                </div>

                <div class="w-9 h-9 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}
                </div>
            </button>

            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-lg py-2 z-50">
                <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="bi bi-person"></i> Profil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>


<script>
    function toggleProfileMenu() {
        document.getElementById('profileMenu').classList.toggle('hidden');
    }

    document.addEventListener('click', function (e) {
        const wrapper = document.getElementById('profileWrapper');
        const menu = document.getElementById('profileMenu');

        if (!wrapper.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>