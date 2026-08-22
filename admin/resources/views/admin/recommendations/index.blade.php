<x-layouts.layout title="Rekomendasi Restock" pageTitle="Rekomendasi Restock">

    <x-slot name="styles">
        <style>
            /* (CSS sama seperti sebelumnya) */
            .insight-card { background: linear-gradient(135deg, #166534 0%, #15803d 55%, #16a34a 100%); position: relative; overflow: hidden; }
            .insight-card::before { content: ''; position: absolute; top: -40px; right: -40px; width: 200px; height: 200px; background: rgba(255,255,255,0.06); border-radius: 50%; }
            .insight-card::after { content: ''; position: absolute; bottom: -60px; right: 60px; width: 260px; height: 260px; background: rgba(255,255,255,0.04); border-radius: 50%; }
            .chart-arrow { opacity: 0.25; }
            .badge-low { background: #fef3c7; color: #92400e; }
            .badge-normal { background: #d1fae5; color: #065f46; }
            .badge-critical { background: #fee2e2; color: #991b1b; }
            .badge-aman { background: #dbeafe; color: #1e40af; }
            .restock-up { color: #16a34a; }
            .restock-zero { color: #6b7280; }
            .product-row { transition: background .15s; }
            .product-row:hover { background: #f9fafb; }
            .sup-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; border: 2px solid #fff; }
        </style>
    </x-slot>

    {{-- HEADER ROW --}}
    <div class="flex gap-4 mb-6">
        <div class="insight-card rounded-2xl p-7 flex-1 relative">
            <span class="inline-flex items-center gap-1.5 bg-white/20 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-4">
                <i class="bi bi-stars text-xs"></i> Intelligence Insight
            </span>
            <h2 class="text-white text-3xl font-extrabold leading-tight mb-3 max-w-xs">
                {{ $insight['headline'] ?? '' }}
            </h2>
            <p class="text-emerald-100 text-sm max-w-xs">
                {{ $insight['description'] ?? '' }}
            </p>
            <svg class="chart-arrow absolute right-10 bottom-6 w-36 h-28" viewBox="0 0 144 112" fill="none">
                <polyline points="0,100 36,72 72,56 108,28 144,4" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="120,4 144,4 144,28" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="bg-white rounded-2xl p-5 w-72 flex flex-col border border-gray-100 shrink-0">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Trend Global</p>
            <p class="text-gray-800 font-bold text-base leading-snug mb-4">Mie Instan Pedas sedang viral di internet.</p>
            @foreach ($trends as $trend)
            <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 mb-2 last:mb-0 hover:border-emerald-200 transition-colors">
                <div class="w-9 h-9 {{ $trend['icon_bg'] ?? '' }} rounded-lg flex items-center justify-center shrink-0">
                    <i class="bi {{ $trend['icon'] ?? '' }} {{ $trend['icon_color'] ?? '' }} text-base"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $trend['name'] ?? '' }}</p>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">
                        <span class="{{ $trend['label_cls'] ?? '' }} font-bold">{{ $trend['label'] ?? '' }}</span>
                    </p>
                </div>
                <button class="w-6 h-6 rounded-full border-2 border-emerald-500 text-emerald-600 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-colors shrink-0">
                    <i class="bi bi-plus text-sm"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>

    {{-- DAFTAR REKOMENDASI --}}
    <div class="bg-white rounded-2xl border border-gray-100 mb-5">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="text-gray-900 font-bold text-base">Daftar Rekomendasi</h3>
                <p class="text-gray-400 text-xs mt-0.5">Prioritas stok berdasarkan risiko kehabisan (out-of-stock).</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-xs font-medium px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="bi bi-funnel text-sm"></i> Filter
                </button>
                <button class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-xs font-medium px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="bi bi-download text-sm"></i> Ekspor
                </button>
            </div>
        </div>

        <div class="grid grid-cols-[2fr_1fr_1fr_1fr] px-6 py-2.5 bg-gray-50 border-b border-gray-100">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Produk</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-center">Stok Sisa</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-center">Prediksi Habis</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-right">Saran Restock</span>
        </div>

        @forelse ($products as $p)
        @php
            $saranCls = $p['saran_cls'] ?? 'restock-up';
            $badgeCls = $p['badge_cls'] ?? 'badge-normal';
        @endphp
        <div class="product-row grid grid-cols-[2fr_1fr_1fr_1fr] items-center px-6 py-4 border-b border-gray-50 last:border-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $p['icon_bg'] ?? '' }} rounded-xl flex items-center justify-center shrink-0">
                    <i class="bi {{ $p['icon'] ?? '' }} {{ $p['icon_color'] ?? '' }} text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $p['name'] ?? '' }}</p>
                    <span class="inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full mt-0.5 {{ $badgeCls }}">
                        {{ $p['badge'] ?? '' }}
                    </span>
                </div>
            </div>
            <div class="text-center">
                <span class="text-lg font-bold text-gray-800">{{ $p['stok'] ?? '-' }}</span>
                <span class="text-xs text-gray-400 ml-1">{{ $p['unit'] ?? '' }}</span>
            </div>
            <div class="text-center">
                <span class="text-sm {{ $p['pred_cls'] ?? 'text-gray-700' }}">{{ $p['prediksi'] ?? '-' }}</span>
            </div>
            <div class="text-right">
                <span class="text-base font-extrabold {{ $saranCls }}">{{ $p['saran'] ?? '+0' }}</span>
                <span class="text-[10px] font-bold {{ $saranCls }} ml-0.5">{{ $p['saran_unit'] ?? 'PCS' }}</span>
                <p class="text-[10px] text-gray-400 mt-0.5">
                    {{ is_array($p['saran_note'] ?? '') ? implode(', ', $p['saran_note']) : ($p['saran_note'] ?? '') }}
                </p>
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center text-gray-400 text-sm">
            <i class="bi bi-inbox text-2xl block mb-2"></i>
            Tidak ada rekomendasi restock saat ini.
        </div>
        @endforelse
    </div>

    {{-- BOTTOM STATS --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Efisiensi Stok</p>
            <div class="flex items-baseline gap-2 mb-1">
                <span class="text-4xl font-extrabold text-gray-900">{{ $stats['efisiensi']['value'] ?? '' }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $stats['efisiensi']['badge_cls'] ?? '' }}">{{ $stats['efisiensi']['badge'] ?? '' }}</span>
            </div>
            <p class="text-xs text-gray-400 leading-relaxed">{{ $stats['efisiensi']['desc'] ?? '' }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Potensi Rugi (Out-of-Stock)</p>
            <div class="flex items-baseline gap-2 mb-1">
                <span class="text-4xl font-extrabold text-gray-900">{{ $stats['potensi_rugi']['value'] ?? '' }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $stats['potensi_rugi']['badge_cls'] ?? '' }}">{{ $stats['potensi_rugi']['badge'] ?? '' }}</span>
            </div>
            <p class="text-xs text-gray-400 leading-relaxed">{{ $stats['potensi_rugi']['desc'] ?? '' }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Supplier Rekanan</p>
            <div class="flex items-center mb-4">
                <div class="sup-avatar bg-emerald-500 text-white -mr-2">W</div>
                <div class="sup-avatar bg-amber-400  text-white -mr-2">G</div>
                <div class="sup-avatar bg-sky-500    text-white -mr-2">I</div>
                <div class="sup-avatar bg-gray-200   text-gray-600">+2</div>
            </div>
            <a href="#" class="inline-flex items-center gap-1.5 text-emerald-600 text-sm font-semibold hover:text-emerald-700 transition-colors">
                <i class="bi bi-telephone-fill text-xs"></i>
                Hubungi Sales
                <i class="bi bi-box-arrow-up-right text-xs"></i>
            </a>
        </div>
    </div>

</x-layouts.layout>
