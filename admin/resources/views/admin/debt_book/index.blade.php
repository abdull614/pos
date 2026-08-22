<x-layouts.layout title="Buku Utang" pageTitle="Buku Utang Digital">

<x-slot name="styles">
    <style>
        .stat-card-green { background: linear-gradient(135deg, #166534 0%, #16a34a 100%); }
        .stat-card-pink { background: linear-gradient(135deg, #be185d 0%, #e11d48 100%); }
        .debitur-card { transition: box-shadow .15s, border-color .15s; }
        .debitur-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); border-color: #d1fae5; }
        .badge-lancar { background: #d1fae5; color: #065f46; }
        .badge-terlambat { background: #fee2e2; color: #991b1b; }
        .filter-tab { transition: all .15s; cursor: pointer; }
        .filter-tab.active { background: #16a34a; color: #fff; }
        .filter-tab:not(.active) { color: #6b7280; }
        .filter-tab:not(.active):hover { background: #f3f4f6; color: #374151; }
        .tips-banner { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
        .tips-phone { background: linear-gradient(135deg, #166534 0%, #16a34a 100%); }
    </style>
</x-slot>

@php
    // Fallback jika controller tidak mengirim data
    if (!isset($summary)) {
        $summary = [
            'total'   => \App\Models\CustomerReceivable::sum('amount'),
            'unpaid'  => \App\Models\CustomerReceivable::where('status', 'unpaid')->sum('remaining'),
            'partial' => \App\Models\CustomerReceivable::where('status', 'partial')->sum('remaining'),
        ];
    }
    if (!isset($status)) $status = request('status', 'all');
    if (!isset($totalDebitur)) {
        $totalDebitur = \App\Models\CustomerReceivable::where('remaining', '>', 0)
            ->distinct('customer_id')
            ->count('customer_id');
    }
@endphp

{{-- Breadcrumb + Header --}}
{{-- FIX: flex-col di mobile supaya judul & tombol "Tambah Debitur" tidak
     berdempetan/kepotong saat layar sempit; kembali jadi baris (row) mulai sm. --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-gray-700 font-medium">Buku Utang</span>
        </nav>
        <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Buku Utang Digital</h1>
        <p class="text-gray-400 text-sm mt-1">Kelola piutang pelanggan dengan presisi. Total saldo piutang tertunggak diperbarui secara real-time.</p>
    </div>
    <a href="{{ route('debt-book.create') }}"
    class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 sm:px-5 py-2.5 rounded-xl shrink-0 transition w-full sm:w-auto">
        <i class="bi bi-plus-lg"></i> <span>Tambah Debitur</span>
    </a>
</div>

{{-- Stat Cards --}}
{{-- FIX: card dibuat lebih ringkas/kecil di layar sempit -- padding, ukuran
     font angka, jarak antar elemen, dan lingkaran dekorasi semua diperkecil
     dengan default (mobile) lalu membesar mulai breakpoint sm. Ini mencegah
     card terasa jangkung/memenuhi layar saat window sempit. --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-7">
    <div class="stat-card-green rounded-2xl p-3.5 sm:p-6 relative overflow-hidden">
        <p class="text-emerald-200 text-[10px] sm:text-xs font-semibold uppercase tracking-widest mb-2 sm:mb-4">Total Piutang Aktif</p>
        <p class="text-emerald-100 text-[10px] sm:text-xs font-medium mb-0.5 sm:mb-1">IDR</p>
        <p class="text-white text-xl sm:text-4xl font-extrabold tracking-tight break-all">{{ number_format($summary['total'], 0, ',', '.') }}</p>
        <div class="absolute -bottom-6 -right-6 w-20 sm:w-28 h-20 sm:h-28 bg-white/10 rounded-full"></div>
    </div>

    <div class="bg-white rounded-2xl p-3.5 sm:p-6 border border-gray-100 flex flex-col justify-between">
        <p class="text-gray-400 text-[10px] sm:text-xs font-semibold uppercase tracking-widest mb-2 sm:mb-4">Jumlah Debitur</p>
        <div>
            <p class="text-gray-900 text-2xl sm:text-5xl font-extrabold">{{ $totalDebitur }}</p>
            <p class="text-gray-400 text-[10px] sm:text-xs mt-0.5 sm:mt-1">Pelanggan Aktif</p>
        </div>
    </div>

    <div class="stat-card-pink rounded-2xl p-3.5 sm:p-6 relative overflow-hidden">
        <p class="text-pink-200 text-[10px] sm:text-xs font-semibold uppercase tracking-widest mb-2 sm:mb-4">Belum Dibayar</p>
        <p class="text-pink-100 text-[10px] sm:text-xs font-medium mb-0.5 sm:mb-1">IDR</p>
        <p class="text-white text-xl sm:text-3xl font-extrabold tracking-tight break-all">{{ number_format($summary['unpaid'], 0, ',', '.') }}</p>
        <p class="text-pink-100 text-[10px] sm:text-xs mt-0.5 sm:mt-1 font-medium uppercase tracking-wider">Tagihan Segera</p>
        <div class="absolute -bottom-6 -right-6 w-20 sm:w-28 h-20 sm:h-28 bg-white/10 rounded-full"></div>
    </div>
</div>

{{-- Daftar Aktif — pakai widget list-card, tinggi tetap + scroll internal --}}
{{-- FIX: tinggi tetap 560px terasa terlalu jangkung di HP (memakan hampir
     seluruh viewport), dipersempit di mobile lalu kembali 560px mulai sm. --}}
<div class="h-[420px] sm:h-[560px] flex flex-col mb-6">
    @include('admin.debt_book.receivables-list')
</div>

{{-- Tips Penagihan Banner --}}
{{-- FIX: flex-row dipaksa membuat kartu telepon "WhatsApp" terjepit kecil
     di HP. Sekarang stack (flex-col) di mobile, jadi row lagi mulai sm. --}}
<div class="tips-banner rounded-2xl p-5 sm:p-7 flex flex-col sm:flex-row items-center gap-6">
    <div class="flex-1 text-center sm:text-left">
        <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3">
            <i class="bi bi-lightning-charge-fill text-xs"></i> Tips Penagihan
        </span>
        <h3 class="text-gray-900 text-lg sm:text-xl font-extrabold leading-snug mb-2">
            Gunakan fitur "Kirim Pengingat" via WhatsApp<br class="hidden sm:block">
            untuk mempermudah penagihan.
        </h3>
        <p class="text-gray-500 text-sm">Pelanggan yang diingatkan 3 hari sebelum jatuh tempo memiliki tingkat pelunasan 40% lebih tinggi.</p>
    </div>
    <div class="tips-phone rounded-2xl p-6 w-full sm:w-40 shrink-0 flex flex-col items-center justify-center gap-3">
        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
            <i class="bi bi-whatsapp text-white text-2xl"></i>
        </div>
        <div class="text-center">
            <p class="text-white text-xs font-bold">WhatsApp</p>
            <p class="text-emerald-200 text-[10px]">Safe work</p>
        </div>
    </div>
</div>

</x-layouts.layout>