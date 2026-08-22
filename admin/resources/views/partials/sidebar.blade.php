<!-- Sidebar -->
<div id="sidebar" class="flex flex-col">
    <!-- Brand -->
    <div class="px-5 py-5 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center shrink-0">
                <i class="bi bi-shop text-white text-sm"></i>
            </div>
            <div>
                <p class="text-gray-900 font-bold text-sm leading-tight">TokoPos Admin</p>
                <p class="text-gray-400 text-[10px] uppercase tracking-wider">Management Suite</p>
            </div>
        </div>
        <button onclick="closeSidebar()"
            class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 transition">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>

    <!-- Nav Items -->
    <ul class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <!-- Dashboard -->
        <li>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ request()->route()->getName() === 'dashboard'
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-grid-1x2 text-base"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Produk -->
        <li>
            <a href="{{ route('products.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'products')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-box-seam text-base"></i>
                <span>Produk</span>
            </a>
        </li>

        <!-- Kategori -->
        <li>
            <a href="{{ route('categories.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'categories')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-tags text-base"></i>
                <span>Kategori</span>
            </a>
        </li>

        <!-- BUKU UTANG (baru) -->
        <li>
            <a href="{{ route('debt-book.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'debt-book')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-journal-bookmark-fill text-base"></i>
                <span>Buku Utang</span>
            </a>
        </li>

        <!-- PENGELUARAN (menu baru) -->
        <li>
            <a href="{{ route('expenditures.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'expenditures')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-wallet2 text-base"></i>
                <span>Pengeluaran</span>
            </a>
        </li>

        <!-- Rekomendasi Restock -->
        <li>
            <a href="{{ route('recommendations.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'recommendations')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-arrow-repeat text-base"></i>
                <span>Rekomendasi Restock</span>
            </a>
        </li>

        <!-- Laporan -->
        <li>
            <a href="{{ route('reports.sales') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
               {{ str_contains(request()->route()->getName(), 'reports')
                   ? 'bg-emerald-50 text-emerald-700 font-semibold'
                   : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                <i class="bi bi-bar-chart-line text-base"></i>
                <span>Laporan</span>
            </a>
        </li>
    </ul>

    <!-- CTA Buka Kasir -->
    <div class="p-3 border-t border-gray-100">
        <a href="#"
           class="flex items-center justify-center gap-2 w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3 rounded-xl transition-all">
            <i class="bi bi-cash-register"></i>
            <span>Buka Kasir</span>
        </a>
    </div>
</div>
