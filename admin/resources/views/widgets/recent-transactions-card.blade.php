<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
            Transaksi Terbaru
        </h3>
        <i class="bi bi-clock-history text-gray-400 text-sm"></i>
    </div>

    <!-- List -->
    <div class="divide-y divide-gray-50">
        @forelse ($transactions as $transaction)
            @php
                $statusConfig = match(strtolower($transaction->status)) {
                    'completed' => ['bg-emerald-100 text-emerald-700', 'Selesai'],
                    'pending'   => ['bg-amber-100 text-amber-700', 'Pending'],
                    'cancelled' => ['bg-red-100 text-red-700', 'Batal'],
                    default     => ['bg-gray-100 text-gray-600', ucfirst($transaction->status)],
                };
            @endphp

            <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50">

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $transaction->invoice_number }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $transaction->kasir->name ?? 'Kasir' }}
                    </p>
                </div>

                <p class="text-sm font-bold text-gray-900">
                    Rp {{ number_format((float) $transaction->total, 0, ',', '.') }}
                </p>

                <span class="text-[10px] font-bold px-2 py-1 rounded-full {{ $statusConfig[0] }}">
                    {{ $statusConfig[1] }}
                </span>

            </div>

        @empty
            <div class="text-center py-10">
                <i class="bi bi-inbox text-gray-300 text-3xl block mb-2"></i>
                <p class="text-gray-400 text-xs">Belum ada transaksi</p>
            </div>
        @endforelse
    </div>

</div>