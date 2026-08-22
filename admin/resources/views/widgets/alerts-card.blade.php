@props([
    'lowStockProducts' => [],
    'debts' => []
])

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-gray-50">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
            Peringatan Stok & Utang
        </h3>
    </div>

    <div class="p-3 sm:p-4 space-y-2 sm:space-y-3">

        {{-- STOK MENIPIS --}}
        @forelse ($lowStockProducts as $product)
            <div class="flex items-center gap-3 p-2.5 sm:p-3 bg-amber-50 border border-amber-100 rounded-xl">
                <i class="bi bi-exclamation-triangle text-amber-600 shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">Stok {{ $product->stock }} unit</p>
                </div>
            </div>
        @empty
            <div class="flex items-center gap-3 p-2.5 sm:p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                <i class="bi bi-check-circle text-emerald-600 shrink-0"></i>
                <p class="text-sm text-emerald-700 font-medium">Semua stok aman</p>
            </div>
        @endforelse

        {{-- UTANG JATUH TEMPO --}}
        @if($debts && count($debts))
            @foreach ($debts as $debt)
                <div class="flex items-center gap-3 p-2.5 sm:p-3 bg-red-50 border border-red-100 rounded-xl">
                    <i class="bi bi-cash text-red-600 shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ $debt->name }}</p>
                        <p class="text-xs text-gray-500">
                            Utang Rp {{ number_format($debt->total_debt, 0, ',', '.') }}
                            @if(isset($debt->days_overdue))
                                &middot; Telat {{ $debt->days_overdue }} hari
                            @endif
                        </p>
                    </div>
                </div>
            @endforeach
        @endif

    </div>

</div>