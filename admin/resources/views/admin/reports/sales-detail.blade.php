<x-layouts.layout title="Detail Transaksi" pageTitle="Detail Transaksi Harian">

    <x-slot name="styles">
    <style>
        .hero-mini {
            background: linear-gradient(135deg, #166534 0%, #15803d 50%, #16a34a 100%);
            position: relative; overflow: hidden;
        }
        .hero-mini::before {
            content:''; position:absolute; top:-30px; right:-30px;
            width:160px; height:160px; background:rgba(255,255,255,0.06); border-radius:50%;
        }
        .payment-bar-track { background:#f3f4f6; border-radius:9999px; height:6px; overflow:hidden; }
        .payment-bar-fill { height:6px; border-radius:9999px; transition:width .5s ease; }
        .trx-card { transition: box-shadow .15s, border-color .15s; }
        .trx-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); border-color:#d1fae5; }
        .timeline-dot {
            width:8px; height:8px; border-radius:9999px; background:#16a34a;
            box-shadow: 0 0 0 4px #d1fae5;
        }
        .timeline-line { width:1px; background:#e5e7eb; }
        .item-chip {
            background:#f9fafb; border:1px solid #f3f4f6;
        }
        .payment-icon {
            width:2rem; height:2rem; border-radius:0.625rem;
            display:flex; align-items:center; justify-content:center;
        }
    </style>
    </x-slot>

    {{-- Breadcrumb + Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-2">
                <a href="{{ route('reports.sales') }}" class="hover:text-emerald-600 transition-colors">Laporan Keuangan</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-gray-700 font-medium">{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
            </h1>
            <p class="text-gray-400 text-xs mt-1">Rincian seluruh transaksi pada tanggal ini</p>
        </div>
        <a href="{{ route('reports.sales') }}"
           class="inline-flex items-center gap-2 border border-gray-200 text-gray-600 text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors shrink-0 self-start sm:self-auto">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Ringkasan Hari --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Total Pendapatan --}}
        <div class="hero-mini rounded-2xl p-6 relative">
            <p class="text-emerald-200 text-[10px] font-bold uppercase tracking-widest mb-3">Total Pendapatan</p>
            <p class="text-white text-3xl font-extrabold tracking-tight">
                Rp {{ number_format($daySummary['total'], 0, ',', '.') }}
            </p>
            <p class="text-emerald-100 text-xs mt-2">{{ $daySummary['count'] }} transaksi selesai</p>
        </div>

        {{-- Jumlah Transaksi --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6 flex flex-col justify-between">
            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-3">Jumlah Transaksi</p>
            <div class="flex items-end gap-2">
                <p class="text-gray-900 text-4xl font-extrabold leading-none">{{ $daySummary['count'] }}</p>
                <p class="text-gray-400 text-xs mb-1">transaksi</p>
            </div>
        </div>

        {{-- Breakdown Metode Bayar --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-3">Metode Pembayaran</p>
            @php
                $paymentMeta = [
                    'cash'     => ['label' => 'Tunai',    'color' => '#16a34a', 'icon' => 'bi-cash-stack'],
                    'transfer' => ['label' => 'Transfer', 'color' => '#2563eb', 'icon' => 'bi-bank'],
                    'qris'     => ['label' => 'QRIS',     'color' => '#9333ea', 'icon' => 'bi-qr-code'],
                    'debt'     => ['label' => 'Kredit/Utang', 'color' => '#dc2626', 'icon' => 'bi-clock-history'],
                ];
                $maxCount = $daySummary['by_payment']->max('jumlah') ?: 1;
            @endphp

            @forelse ($daySummary['by_payment'] as $pm)
                @php
                    $meta = $paymentMeta[$pm->payment_type] ?? ['label' => ucfirst($pm->payment_type ?? '-'), 'color' => '#6b7280', 'icon' => 'bi-wallet2'];
                    $width = round(($pm->jumlah / $maxCount) * 100);
                @endphp
                <div class="mb-3 last:mb-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-gray-600 flex items-center gap-1.5">
                            <i class="bi {{ $meta['icon'] }} text-[11px]" style="color: {{ $meta['color'] }}"></i>
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-xs font-bold text-gray-800">{{ $pm->jumlah }}</span>
                    </div>
                    <div class="payment-bar-track">
                        <div class="payment-bar-fill" style="width: {{ $width }}%; background: {{ $meta['color'] }}"></div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-400">Belum ada data.</p>
            @endforelse
        </div>

    </div>

    {{-- Daftar Transaksi (Timeline) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-gray-900">Riwayat Transaksi</h2>
            <span class="text-xs text-gray-400 font-medium">{{ $transactions->total() }} data</span>
        </div>

        <div class="p-5 sm:p-6">
            @forelse ($transactions as $trx)
            @php
                $meta = $paymentMeta[$trx->payment_type] ?? ['label' => ucfirst($trx->payment_type ?? '-'), 'color' => '#6b7280', 'icon' => 'bi-wallet2'];
            @endphp
            <div class="flex gap-4 {{ !$loop->last ? 'pb-5' : '' }}">
                {{-- Timeline marker --}}
                <div class="flex flex-col items-center shrink-0 pt-1.5">
                    <span class="timeline-dot"></span>
                    @if(!$loop->last)
                        <span class="timeline-line flex-1 mt-1.5"></span>
                    @endif
                </div>

                {{-- Card --}}
                <div class="trx-card bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $trx->invoice_number ?? '#'.$trx->id }}
                                </p>
                                <span class="text-[11px] font-semibold text-gray-400">
                                    {{ $trx->sold_at->format('H:i') }}
                                </span>
                                <span class="payment-icon" style="background: {{ $meta['color'] }}1A">
                                    <i class="bi {{ $meta['icon'] }} text-[11px]" style="color: {{ $meta['color'] }}"></i>
                                </span>
                                <span class="text-[11px] font-medium text-gray-500">{{ $meta['label'] }}</span>
                            </div>
                            @if($trx->customer)
                            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                <i class="bi bi-person text-[11px]"></i> {{ $trx->customer->name }}
                            </p>
                            @endif
                        </div>
                        <p class="text-base font-extrabold text-emerald-600 shrink-0">
                            Rp {{ number_format($trx->total, 0, ',', '.') }}
                        </p>
                    </div>

                    {{-- Item chips --}}
                    <div class="flex flex-wrap gap-1.5 pt-3 border-t border-dashed border-gray-100">
                        @foreach ($trx->saleItems as $item)
                        <span class="item-chip text-[11px] text-gray-600 font-medium px-2.5 py-1 rounded-lg">
                            {{ $item->product->name ?? 'Produk dihapus' }}
                            <span class="text-gray-400">&times;{{ $item->quantity }}</span>
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-16">
                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-receipt text-gray-300 text-2xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-500">Tidak ada transaksi</p>
                <p class="text-xs text-gray-400 mt-1">Belum ada transaksi tercatat pada tanggal ini.</p>
            </div>
            @endforelse
        </div>

        @if ($transactions->hasPages())
        <div class="px-5 sm:px-6 py-4 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

</x-layouts.layout>