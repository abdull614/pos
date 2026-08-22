@props([
    'title' => 'Produk Terlaris',
    'products' => []
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col']) }}>

    <!-- Header -->
    <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-3 sm:pb-4 flex items-center justify-between border-b border-gray-50">
        <h3 class="text-sm font-bold text-gray-900">{{ $title }}</h3>
    </div>

    <!-- List -->
    <div class="p-2 sm:p-3 space-y-1 flex-1">
        @forelse ($products as $product)
            <div class="flex items-center gap-2 sm:gap-3 p-2 rounded-xl hover:bg-gray-50 transition">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="bi bi-box text-gray-400 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-900 truncate">
                        {{ $product->name }}
                    </p>
                    <p class="text-[10px] text-gray-400">
                        {{ $product->total_sold }} Terjual
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs font-bold text-emerald-600">
                        Rp {{ number_format($product->revenue / 1000, 0) }}k
                    </p>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <i class="bi bi-inbox text-gray-300 text-3xl block mb-2"></i>
                <p class="text-gray-400 text-xs">Belum ada data</p>
            </div>
        @endforelse
    </div>

</div>