<x-layouts.layout title="Laporan Keuangan" pageTitle="Laporan Keuangan">

    <x-slot name="styles">
    <style>
        .hero-card {
            background: linear-gradient(135deg, #166534 0%, #15803d 50%, #16a34a 100%);
            position: relative; overflow: hidden;
        }
        .hero-card.negative {
            background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%);
        }
        .hero-card::before {
            content:''; position:absolute; top:-40px; right:-40px;
            width:220px; height:220px; background:rgba(255,255,255,0.05); border-radius:50%;
        }
        .hero-card::after {
            content:''; position:absolute; bottom:-60px; left:40px;
            width:180px; height:180px; background:rgba(255,255,255,0.04); border-radius:50%;
        }
        .revenue-card { background:#fff; }
        .restock-card {
            background: linear-gradient(135deg, #831843 0%, #be185d 100%);
        }
        .optimize-card {
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
        }
        .activity-row { transition: background .12s; }
        .activity-row:hover { background:#f9fafb; }
        .progress-bar-track { background:#e5e7eb; border-radius:9999px; height:8px; }
        .progress-bar-fill  { background:#16a34a; border-radius:9999px; height:8px; transition:width .6s ease; }
    </style>
    </x-slot>

    {{-- ── Top: Hero + Revenue ─────────────────────────────────────── --}}
    <div class="flex flex-col lg:flex-row gap-4 mb-5">

        {{-- Hero — Total Keuntungan Bersih --}}
        <div class="hero-card {{ $netProfit < 0 ? 'negative' : '' }} rounded-2xl p-6 sm:p-8 flex-1 relative">
            <p class="text-emerald-200 text-[10px] font-bold uppercase tracking-widest mb-4">Total Keuntungan Bersih</p>

            <div class="flex items-end gap-4 mb-6">
                <div>
                    <p class="text-white/70 text-base font-medium mb-0.5">Rp</p>
                    <p class="text-white text-4xl sm:text-5xl font-extrabold tracking-tight leading-none">
                        {{ number_format($netProfit, 0, ',', '.') }}
                    </p>
                </div>
                <svg class="opacity-20 w-20 h-20 sm:w-24 sm:h-24 text-white shrink-0 mb-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2 10h20v2H2zM4 13h2v5H4zm4 0h2v5H8zm4 0h2v5h-2zm4 0h2v5h-2zM2 19h20v2H2zM12 2L2 8h20L12 2z"/>
                </svg>
            </div>

            <div class="inline-flex items-center gap-2 bg-white/15 rounded-xl px-4 py-2">
                <span class="inline-flex items-center gap-1 bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                    <i class="bi {{ $growthPercent >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }} text-[10px]"></i>
                    {{ number_format(abs($growthPercent), 1) }}%
                </span>
                <span class="text-emerald-100 text-xs font-medium">Dari periode sebelumnya</span>
            </div>
        </div>

        {{-- Revenue Right --}}
        <div class="revenue-card rounded-2xl border border-gray-100 shadow-sm p-6 lg:w-64 flex flex-col justify-center gap-5">

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Penjualan</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                    Rp {{ number_format($totalSales, 0, ',', '.') }}
                </p>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Pengeluaran</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-rose-600">
                    Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                </p>
            </div>

        </div>

    </div>

    {{-- ── Middle: Detail Penjualan + Right Column ────────────────── --}}
    <div class="flex flex-col lg:flex-row gap-4 mb-5">

        {{-- Detail Penjualan --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex-1 overflow-hidden">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 sm:px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-gray-900">Detail Penjualan</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Analisis harian volume transaksi</p>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <form action="{{ route('reports.sales') }}" method="GET" class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors">
                            <i class="bi bi-funnel text-xs"></i> Filter
                        </button>
                    </form>
                    <div class="relative inline-block">
                        <button type="button" onclick="toggleExcelMenu()"
                            class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-xs font-semibold px-3.5 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="bi bi-file-earmark-excel text-xs"></i> Unduh Excel
                        </button>

                        <div id="excelMenu" class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4">
                            <form action="{{ route('reports.sales.excel') }}" method="GET">
                                <p class="text-xs font-bold text-gray-700 mb-3">Pilih Rentang Data</p>

                                <label class="flex items-center gap-2 text-xs text-gray-600 mb-2 cursor-pointer">
                                    <input type="radio" name="range_mode" value="custom" checked
                                        onchange="document.getElementById('excelDateFields').classList.remove('opacity-40','pointer-events-none')">
                                    Rentang tanggal tertentu
                                </label>
                                <div id="excelDateFields" class="grid grid-cols-2 gap-2 mb-3 pl-6">
                                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                                    <input type="date" name="date_to" value="{{ $dateTo }}"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                                </div>

                                <label class="flex items-center gap-2 text-xs text-gray-600 mb-4 cursor-pointer">
                                    <input type="radio" name="range_mode" value="all"
                                        onchange="document.getElementById('excelDateFields').classList.add('opacity-40','pointer-events-none')">
                                    Semua data (sepanjang waktu)
                                </label>

                                <button type="submit"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold py-2 rounded-lg transition-colors">
                                    <i class="bi bi-download"></i> Export Sekarang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-5 sm:px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Tanggal</th>
                            <th class="text-right px-4 sm:px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Terjual</th>
                            <th class="text-right px-4 sm:px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($sales as $item)
                        <tr class="activity-row hover:cursor-pointer" onclick="window.location='{{ route('reports.sales.detail', $item->date) }}'">
                            <td class="px-5 sm:px-6 py-3.5 sm:py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
                                        <i class="bi bi-grid text-emerald-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">
                                            {{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}
                                        </p>
                                        <p class="text-xs text-gray-400">{{ $item->count }} transaksi</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5 sm:py-4 text-right">
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ number_format($item->count, 0, ',', '.') }} unit
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5 sm:py-4 text-right">
                                <span class="text-sm font-extrabold text-gray-900">
                                    Rp {{ number_format($item->total, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-14 text-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <i class="bi bi-bar-chart text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-500">Belum ada data penjualan</p>
                                <p class="text-xs text-gray-400 mt-1">Coba ubah rentang tanggal filter</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    @if ($sales->count() > 0)
                    <tfoot>
                        <tr class="border-t-2 border-dashed border-gray-200">
                            <td class="px-5 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Total</td>
                            <td class="px-4 sm:px-6 py-3 text-right text-xs font-bold text-gray-600">
                                {{ number_format($totalTransactions, 0, ',', '.') }} unit
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-right text-sm font-extrabold text-emerald-600">
                                Rp {{ number_format($totalSales, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="flex flex-col gap-4 lg:w-64">

            {{-- Pengeluaran Restock --}}
            <div class="restock-card rounded-2xl p-5 relative overflow-hidden">
                <p class="text-pink-200 text-[10px] font-bold uppercase tracking-widest mb-0.5">Pengeluaran Terbaru</p>
                <p class="text-white/60 text-xs mb-4">Sesuai rentang tanggal filter</p>

                <div class="space-y-2.5">
                    @forelse ($restocks as $r)
                    <div class="bg-white/10 hover:bg-white/15 transition-colors rounded-xl px-3.5 py-2.5 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-white text-xs font-semibold truncate">{{ $r->description }}</p>
                            <p class="text-pink-200 text-[10px] mt-0.5">
                                {{ \Carbon\Carbon::parse($r->expense_date)->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <span class="text-white text-xs font-extrabold shrink-0">
                            Rp {{ number_format($r->amount / 1000, 0, ',', '.') }}k
                        </span>
                    </div>
                    @empty
                    <p class="text-pink-100 text-xs text-center py-4">Belum ada pengeluaran di periode ini.</p>
                    @endforelse
                </div>

                <div class="absolute -bottom-8 -right-8 w-24 h-24 bg-white/05 rounded-full"></div>
            </div>

            {{-- Efisiensi Margin --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="text-sm font-extrabold text-gray-900 mb-4">Efisiensi Margin</h3>

                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-500 font-medium">Target Profit</span>
                    <span class="text-xs font-extrabold text-gray-800">{{ $targetMargin }}%</span>
                </div>
                <div class="progress-bar-track mb-4">
                    <div class="progress-bar-fill" style="width: {{ $marginProgress }}%"></div>
                </div>

                <div class="bg-gray-50 rounded-xl p-3.5 flex items-start gap-2">
                    <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <i class="bi bi-graph-up-arrow text-emerald-600 text-[10px]"></i>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Margin Anda pada periode ini adalah
                        <span class="font-bold {{ $marginPercent >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($marginPercent, 1) }}%
                        </span>.
                    </p>
                </div>
            </div>

            {{-- Optimalkan Inventori --}}
            <div class="optimize-card rounded-2xl p-5 relative overflow-hidden">
                <h3 class="text-white text-sm font-extrabold mb-1.5">Optimalkan Inventori</h3>
                <p class="text-emerald-100 text-xs leading-relaxed mb-4">
                    Kurangi stok yang jarang laku untuk meningkatkan arus kas.
                </p>
                <a href="{{ route('recommendations.index') }}"
                    class="inline-flex items-center gap-1.5 bg-white text-emerald-700 text-xs font-bold px-4 py-2 rounded-xl hover:bg-emerald-50 transition-colors">
                    <i class="bi bi-arrow-repeat text-xs"></i> Cek Rekomendasi
                </a>
                <div class="absolute -bottom-6 -right-6 w-20 h-20 bg-white/10 rounded-full"></div>
                <div class="absolute bottom-4 right-4 opacity-10">
                    <i class="bi bi-boxes text-white text-5xl"></i>
                </div>
            </div>

        </div>

    </div>

    {{-- ── Aktivitas Terakhir ───────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 sm:px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-extrabold text-gray-900">Aktivitas Terakhir</h2>
            <div class="flex items-center gap-4 self-start sm:self-auto">
                <span class="flex items-center gap-1.5 text-xs text-gray-500 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Penjualan
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 font-medium">
                    <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> Pengeluaran
                </span>
            </div>
        </div>

        <div class="divide-y divide-gray-50">
            @forelse ($activities as $act)
            <div class="activity-row flex items-center gap-3 sm:gap-4 px-5 sm:px-6 py-4">

                <div class="w-9 h-9 sm:w-10 sm:h-10 {{ $act['icon_bg'] }} rounded-xl flex items-center justify-center shrink-0">
                    <i class="bi {{ $act['icon'] }} {{ $act['icon_color'] }} text-sm sm:text-base"></i>
                </div>

                <div class="w-24 sm:w-28 shrink-0">
                    <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wide leading-tight">
                        {{ $act['time_label'] }}
                    </p>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $act['title'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $act['sub'] }}</p>
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-sm font-extrabold {{ $act['type'] === 'in' ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $act['type'] === 'in' ? '+' : '-' }} Rp {{ number_format($act['amount'], 0, ',', '.') }}
                    </p>
                </div>

            </div>
            @empty
            <div class="px-5 sm:px-6 py-10 text-center text-gray-400 text-sm">
                Belum ada aktivitas pada periode ini.
            </div>
            @endforelse
        </div>

    </div>

    <script>
    function toggleExcelMenu() {
        document.getElementById('excelMenu').classList.toggle('hidden');
    }
    document.addEventListener('click', function (e) {
        const menu = document.getElementById('excelMenu');
        const isButton = e.target.closest('[onclick="toggleExcelMenu()"]');
        if (menu && !menu.contains(e.target) && !isButton) menu.classList.add('hidden');
    });
    </script>
</x-layouts.layout>