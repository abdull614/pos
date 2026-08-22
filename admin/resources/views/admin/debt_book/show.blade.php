<x-layouts.layout title="Detail Utang" pageTitle="Detail Utang Pelanggan" no-scroll>

    @php
        // Label & warna metode pembayaran, dipakai di badge timeline.
        $metodeLabel = fn ($m) => match ($m) {
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'bank_transfer' => 'Transfer',
            'check' => 'Cek',
            default => 'Lainnya',
        };
        $metodeIcon = fn ($m) => match ($m) {
            'cash' => 'bi-cash-stack',
            'qris' => 'bi-qr-code',
            'bank_transfer' => 'bi-bank',
            'check' => 'bi-receipt',
            default => 'bi-three-dots',
        };

        // Gabungkan utang baru + pembayaran jadi satu timeline, urut dari yang terbaru.
        $timeline = collect();

        foreach ($receivables as $rec) {
            // Keterangan: kalau piutang berasal dari transaksi kasir, tampilkan
            // daftar produk yang dibeli. Kalau piutang manual (Tambah Debitur),
            // pakai catatan (notes) yang diisi user.
            if ($rec->salesTransaction && $rec->salesTransaction->saleItems->isNotEmpty()) {
                $description = $rec->salesTransaction->saleItems
                    ->map(function ($item) {
                        $itemName = $item->product->name ?? $item->name ?? 'Item';
                        return $itemName . ' x' . $item->quantity;
                    })
                    ->join(', ');
            } else {
                $description = $rec->notes ?: '-';
            }

            $timeline->push([
                'date'        => $rec->created_at,
                'due_date'    => $rec->due_date,
                'type'        => 'debt',
                'amount'      => $rec->amount,
                'remaining'   => $rec->remaining,
                'description' => $description,
                'invoice'     => $rec->salesTransaction->invoice_number ?? null,
                'status'      => $rec->status,
            ]);
        }

        foreach ($paymentHistories as $payment) {
            $timeline->push([
                'date'          => $payment->paid_at,
                'due_date'      => null,
                'type'          => 'payment',
                'amount'        => $payment->amount,
                'remaining'     => null,
                'description'   => $payment->notes ?? 'Pembayaran cicilan',
                'invoice'       => null,
                'status'        => null,
                'metode'        => $payment->payment_method,
                'reference'     => $payment->reference,
                'recorded_by'   => $payment->recordedBy->name ?? null,
                // BARU: URL lengkap bukti pembayaran (null kalau tidak ada).
                // Otomatis tersedia lewat accessor proof_of_payment_url di model
                // PaymentHistory, tidak perlu query tambahan.
                'proof'         => $payment->proof_of_payment_url ?? null,
            ]);
        }

        $timeline = $timeline->sortByDesc('date')->values();

        $totalDebt = $receivables->sum('amount');
        $totalRemaining = $receivables->sum('remaining');
        $totalPaid = $totalDebt - $totalRemaining;
    @endphp

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ route('debt-book.index') }}" class="hover:text-gray-600 transition-colors">Buku Utang</a>
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="text-gray-700 font-medium">{{ $customer->name }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('debt-book.index') }}"
               class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition shadow-sm shrink-0">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">Detail Utang Pelanggan</h1>
                <p class="text-xs text-gray-400 mt-0.5">Riwayat lengkap transaksi utang & pembayaran</p>
            </div>
        </div>

        @php
            $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone ?? '');
            $waMessage = "Halo *{$customer->name}*,%0A%0AKami mengingatkan sisa utang Anda sebesar *Rp " . number_format($totalRemaining, 0, ',', '.') . "*. Terima kasih 🙏";
            $waLink = $phoneNumber ? "https://wa.me/{$phoneNumber}?text={$waMessage}" : null;
        @endphp
        @if ($totalRemaining > 0 && $waLink)
        <a href="{{ $waLink }}" target="_blank"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 sm:px-5 py-2.5 rounded-xl shrink-0 transition">
            <i class="bi bi-whatsapp"></i> <span class="hidden sm:inline">Kirim Pengingat</span>
        </a>
        @endif
    </div>

    {{-- Info Pelanggan + Stat --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        <div class="lg:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                <i class="bi bi-person-fill text-emerald-600 text-2xl"></i>
            </div>
            <p class="font-bold text-gray-900 text-base">{{ $customer->name }}</p>
            <p class="text-xs text-gray-400 mt-1">
                <i class="bi bi-telephone"></i> {{ $customer->phone ?? 'Tidak ada nomor' }}
            </p>
            <p class="text-xs text-gray-400 mt-1 max-w-55">
                <i class="bi bi-geo-alt"></i> {{ $customer->address ?? 'Alamat belum diisi' }}
            </p>
            @if ($customer->email)
            <p class="text-xs text-gray-400 mt-1">
                <i class="bi bi-envelope"></i> {{ $customer->email }}
            </p>
            @endif
            @if ($customer->customer_type)
            <span class="mt-3 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 capitalize">
                {{ $customer->customer_type === 'reseller' ? 'Reseller' : 'Pelanggan Reguler' }}
            </span>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Total Utang</p>
            <p class="text-2xl font-extrabold text-gray-900">Rp {{ number_format($totalDebt, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">Akumulasi seluruh transaksi ({{ $receivables->count() }} transaksi)</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-gray-400">Sisa Utang</p>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $totalRemaining > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $totalRemaining > 0 ? 'Belum Lunas' : 'Lunas' }}
                </span>
            </div>
            <p class="text-2xl font-extrabold {{ $totalRemaining > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                Rp {{ number_format($totalRemaining, 0, ',', '.') }}
            </p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $totalDebt > 0 ? min(100, round($totalPaid / $totalDebt * 100)) : 0 }}%"></div>
            </div>
            <p class="text-[11px] text-gray-400 mt-1.5">Terbayar Rp {{ number_format($totalPaid, 0, ',', '.') }} ({{ $paymentHistories->count() }}x pembayaran)</p>
        </div>
    </div>

    {{-- Timeline Utang & Pembayaran --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2.5">
            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                <i class="bi bi-clock-history text-gray-500 text-sm"></i>
            </div>
            <h2 class="text-sm font-bold text-gray-900">Riwayat Transaksi</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="text-left px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="text-left px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="text-left px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Metode / Referensi</th>
                        <th class="text-right px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nominal</th>
                        <th class="text-right px-5 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sisa Utang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($timeline as $item)
                        @php $isPayment = $item['type'] === 'payment'; @endphp
                        <tr class="hover:bg-gray-50/70 transition align-top">
                            {{-- Tanggal --}}
                            <td class="px-5 sm:px-6 py-3.5 text-gray-500 whitespace-nowrap">
                                @if ($isPayment)
                                    {{ $item['date']?->translatedFormat('d M Y, H:i') ?? '-' }}
                                @else
                                    {{ ($item['due_date'] ?? $item['date'])?->translatedFormat('d M Y') ?? '-' }}
                                    <div class="text-[10px] text-gray-300">Jatuh tempo</div>
                                @endif
                            </td>

                            {{-- Tipe --}}
                            <td class="px-5 sm:px-6 py-3.5 whitespace-nowrap">
                                @if ($isPayment)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                        <i class="bi bi-arrow-down-left"></i> Pembayaran
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700">
                                        <i class="bi bi-arrow-up-right"></i> Utang Baru
                                    </span>
                                    @if ($item['status'])
                                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide {{ $item['status'] === 'paid' ? 'text-emerald-500' : ($item['status'] === 'partial' ? 'text-amber-500' : 'text-gray-400') }}">
                                            {{ $item['status'] === 'paid' ? 'Lunas' : ($item['status'] === 'partial' ? 'Cicilan' : 'Belum Bayar') }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- Keterangan --}}
                            <td class="px-5 sm:px-6 py-3.5 text-gray-600 max-w-xs" title="{{ $item['description'] }}">
                                <div class="truncate">{{ $item['description'] }}</div>
                                @if (!$isPayment && $item['invoice'])
                                    <div class="text-[10px] text-gray-300 mt-0.5">Invoice: {{ $item['invoice'] }}</div>
                                @endif
                                @if ($isPayment && $item['recorded_by'])
                                    <div class="text-[10px] text-gray-300 mt-0.5">Dicatat oleh: {{ $item['recorded_by'] }}</div>
                                @endif
                            </td>

                            {{-- Metode / Referensi --}}
                            <td class="px-5 sm:px-6 py-3.5 whitespace-nowrap">
                                @if ($isPayment)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                        <i class="bi {{ $metodeIcon($item['metode']) }}"></i> {{ $metodeLabel($item['metode']) }}
                                    </span>
                                    @if ($item['reference'])
                                        <div class="text-[10px] text-gray-400 mt-1">Ref: {{ $item['reference'] }}</div>
                                    @endif
                                    @if ($item['proof'] ?? null)
                                        <a href="{{ $item['proof'] }}" target="_blank"
                                        class="inline-flex items-center gap-1 mt-1 text-[10px] font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                            <i class="bi bi-image"></i> Lihat Bukti
                                        </a>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Nominal --}}
                            <td class="px-5 sm:px-6 py-3.5 text-right font-semibold whitespace-nowrap {{ $isPayment ? 'text-emerald-600' : 'text-gray-900' }}">
                                {{ $isPayment ? '-' : '' }} Rp {{ number_format($item['amount'], 0, ',', '.') }}
                            </td>

                            {{-- Sisa Utang --}}
                            <td class="px-5 sm:px-6 py-3.5 text-right text-gray-500 whitespace-nowrap">
                                {{ $isPayment ? '—' : 'Rp ' . number_format($item['remaining'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-14">
                                <i class="bi bi-inbox text-gray-300 text-3xl block mb-2"></i>
                                <p class="text-gray-400 text-sm">Belum ada riwayat transaksi</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layouts.layout>