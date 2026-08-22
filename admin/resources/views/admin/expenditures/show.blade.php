<x-layouts.layout title="Detail Pengeluaran" pageTitle="Detail Pengeluaran">
    <div class="container mx-auto w-full pb-12">

        {{-- Breadcrumb + Actions --}}
        <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('expenditures.index') }}" class="hover:text-emerald-600 transition flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Pengeluaran
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-700 font-medium">Detail</span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('expenditures.edit', $expenditure) }}"
                   class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium shadow-sm hover:shadow-md hover:border-emerald-300 hover:text-emerald-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </a>
                <form action="{{ route('expenditures.destroy', $expenditure) }}" method="POST"
                      onsubmit="return confirm('Yakin hapus pengeluaran ini?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-red-500 px-4 py-2 rounded-xl text-sm font-medium shadow-sm hover:shadow-md hover:bg-red-50 hover:border-red-200 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        {{-- Hero Amount Card --}}
        <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-red-600 to-rose-700 shadow-lg shadow-red-200/50 mb-6" style="background-color:#dc2626;">
            <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/10"></div>
            <div class="absolute -right-2 top-16 w-20 h-20 rounded-full bg-white/10"></div>

            <div class="relative px-6 sm:px-8 py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-red-50 text-sm font-medium mb-1">Total Pengeluaran</p>
                    <p class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight" style="color:#ffffff;">
                        Rp {{ number_format($expenditure->amount, 0, ',', '.') }}
                    </p>
                    <p class="text-red-50 text-sm mt-2" style="color:#fee2e2;">
                        {{ $expenditure->expense_date->translatedFormat('l, d F Y') }}
                    </p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold bg-white/20 backdrop-blur text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
                    {{ $expenditure->category ?? 'Tanpa Kategori' }}
                </span>
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            {{-- Deskripsi --}}
            <div class="sm:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 9h1M9 13h6M9 17h6"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Deskripsi</p>
                        <p class="text-gray-800 leading-relaxed">{{ $expenditure->description }}</p>
                    </div>
                </div>
            </div>

            {{-- Dicatat oleh --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Dicatat oleh</p>
                        <p class="text-gray-800 font-medium">{{ $expenditure->user->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Dibuat pada --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Dibuat pada</p>
                        <p class="text-gray-800 font-medium">{{ $expenditure->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu Pembelian Terkait --}}
        @if($expenditure->purchase)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-linear-to-r from-gray-50 to-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-800">Pembelian Terkait</h2>
                </div>

                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-4 px-6 py-5">
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">No. Invoice</p>
                        <p class="text-gray-800 font-medium">{{ $expenditure->purchase->invoice ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Supplier</p>
                        <p class="text-gray-800 font-medium">{{ $expenditure->purchase->supplier ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Pembelian</p>
                        <p class="text-gray-800 font-semibold">Rp {{ number_format($expenditure->purchase->total, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Status</p>
                        @php
                            $status = $expenditure->purchase->status ?? null;
                            $statusColor = match(true) {
                                $status === 'lunas' || $status === 'paid' => 'bg-emerald-50 text-emerald-700',
                                $status === 'pending' => 'bg-amber-50 text-amber-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                            {{ $status ?? '-' }}
                        </span>
                    </div>
                </div>

                {{-- Foto Nota --}}
                @if($expenditure->purchase->receipt_image)
                    <div class="px-6 pb-5">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Foto Nota</p>
                        @if(str_ends_with($expenditure->purchase->receipt_image, '.pdf'))
                            <a href="{{ Storage::url($expenditure->purchase->receipt_image) }}" target="_blank"
                               class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200 transition font-medium text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                Lihat Nota (PDF)
                            </a>
                        @else
                            <a href="{{ Storage::url($expenditure->purchase->receipt_image) }}" target="_blank" class="inline-block group">
                                <img src="{{ Storage::url($expenditure->purchase->receipt_image) }}" alt="Nota Pembelian"
                                     class="w-44 h-44 object-cover rounded-xl border border-gray-200 shadow-sm group-hover:shadow-md group-hover:scale-[1.02] transition">
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Tabel Item --}}
                @if($expenditure->purchase->items->count() > 0)
                    <div class="border-t border-gray-100">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Produk</th>
                                    <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                                    <th class="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Harga Beli</th>
                                    <th class="px-6 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($expenditure->purchase->items as $item)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-2.5 text-sm text-gray-800">{{ $item->product->name ?? '-' }}</td>
                                    <td class="px-6 py-2.5 text-sm text-gray-600">{{ $item->quantity }}</td>
                                    <td class="px-6 py-2.5 text-sm text-gray-800">
                                        Rp {{ number_format($item->purchase_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-2.5 text-sm text-gray-800 text-right font-medium">
                                        Rp {{ number_format($item->quantity * $item->purchase_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-100">
                                    <td colspan="3" class="px-6 py-3 text-sm font-semibold text-gray-700 text-right">Total Pembelian</td>
                                    <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">
                                        Rp {{ number_format($expenditure->purchase->items->sum(fn($i) => $i->quantity * $i->purchase_price), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.layout>