<x-layouts.layout title="Buku Utang" pageTitle="Buku Utang Digital">

{{-- styles --}}
    <x-slot name="styles">
        <style>
            .stat-card-green {
                background: linear-gradient(135deg, #166534 0%, #16a34a 100%);
            }
            .stat-card-pink {
                background: linear-gradient(135deg, #be185d 0%, #e11d48 100%);
            }
            .debitur-card { transition: box-shadow .15s, border-color .15s; }
            .debitur-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); border-color: #d1fae5; }
            .badge-lancar    { background: #d1fae5; color: #065f46; }
            .badge-terlambat { background: #fee2e2; color: #991b1b; }
            .filter-tab { transition: all .15s; cursor: pointer; }
            .filter-tab.active { background: #16a34a; color: #fff; }
            .filter-tab:not(.active) { color: #6b7280; }
            .filter-tab:not(.active):hover { background: #f3f4f6; color: #374151; }
            .tips-banner { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
            .tips-phone  { background: linear-gradient(135deg, #166534 0%, #16a34a 100%); }
        </style>
    </x-slot>

{{-- Breadcrumb + Header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-gray-700 font-medium">Buku Utang</span>
        </nav>
        <h1 class="text-2xl font-extrabold text-gray-900">Buku Utang Digital</h1>
        <p class="text-gray-400 text-sm mt-1 max-w-sm">
            Kelola piutang pelanggan dengan presisi. Total saldo piutang tertunggak diperbarui secara real-time.
        </p>
    </div>
    <a href="#"
        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-colors shrink-0">
        <i class="bi bi-person-plus-fill"></i>
        Tambah Debitur Baru
    </a>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-3 gap-4 mb-7">

    {{-- Total Piutang Aktif --}}
    <div class="stat-card-green rounded-2xl p-6 relative overflow-hidden">
        <p class="text-emerald-200 text-xs font-semibold uppercase tracking-widest mb-4">Total Piutang Aktif</p>
        <p class="text-emerald-100 text-xs font-medium mb-1">IDR</p>
        <p class="text-white text-4xl font-extrabold tracking-tight">
            {{ number_format($summary['total'], 0, ',', '.') }}
        </p>
        <div class="absolute -bottom-6 -right-6 w-28 h-28 bg-white/10 rounded-full"></div>
    </div>

    {{-- Jumlah Debitur --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 flex flex-col justify-between">
        <p class="text-gray-400 text-xs font-semibold uppercase tracking-widest mb-4">Jumlah Debitur</p>
        <div>
            <p class="text-gray-900 text-5xl font-extrabold">{{ $receivables->total() }}</p>
            <p class="text-gray-400 text-xs mt-1">Pelanggan Aktif</p>
        </div>
    </div>

    {{-- Belum Dibayar --}}
    <div class="stat-card-pink rounded-2xl p-6 relative overflow-hidden">
        <p class="text-pink-200 text-xs font-semibold uppercase tracking-widest mb-4">Belum Dibayar</p>
        <p class="text-pink-100 text-xs font-medium mb-1">IDR</p>
        <p class="text-white text-3xl font-extrabold tracking-tight">
            {{ number_format($summary['unpaid'], 0, ',', '.') }}
        </p>
        <p class="text-pink-100 text-xs mt-1 font-medium uppercase tracking-wider">Tagihan Segera</p>
        <div class="absolute -bottom-6 -right-6 w-28 h-28 bg-white/10 rounded-full"></div>
    </div>

</div>

{{-- Filter + Daftar Aktif --}}
<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-gray-900 font-bold text-base">Daftar Aktif</h2>
        <div class="flex items-center gap-2">
            {{-- Filter Status --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <a href="{{ route('reports.receivables', ['status' => 'all']) }}"
                    class="filter-tab text-xs font-semibold px-3 py-1.5 rounded-md {{ $status === 'all' ? 'active' : '' }}">
                    Semua
                </a>
                <a href="{{ route('reports.receivables', ['status' => 'unpaid']) }}"
                    class="filter-tab text-xs font-semibold px-3 py-1.5 rounded-md {{ $status === 'unpaid' ? 'active' : '' }}">
                    Belum Bayar
                </a>
                <a href="{{ route('reports.receivables', ['status' => 'partial']) }}"
                    class="filter-tab text-xs font-semibold px-3 py-1.5 rounded-md {{ $status === 'partial' ? 'active' : '' }}">
                    Sebagian
                </a>
                <a href="{{ route('reports.receivables', ['status' => 'paid']) }}"
                    class="filter-tab text-xs font-semibold px-3 py-1.5 rounded-md {{ $status === 'paid' ? 'active' : '' }}">
                    Lunas
                </a>
            </div>
        </div>
    </div>

    {{-- Grid 2 kolom --}}
    <div class="grid grid-cols-2 gap-3">
        {{-- Daftar Aktif (Gaya Baru) --}}
<div class="space-y-3">
    @forelse ($receivables as $receivable)
    @php
        $customer = $receivable->customer;
        $totalDebt = $receivable->amount;
        $remaining = $receivable->remaining;
        $paid = $totalDebt - $remaining;
        $percentPaid = $totalDebt > 0 ? round(($paid / $totalDebt) * 100) : 0;

        // Warna status berdasarkan persentase dan sisa
        if ($remaining <= 0) {
            $statusColor = 'green';
            $statusText = 'Lunas';
        } elseif ($percentPaid >= 50) {
            $statusColor = 'yellow';
            $statusText = 'Cicilan Aktif';
        } else {
            $statusColor = 'red';
            $statusText = 'Belum Dibayar';
        }

        // Nomor WhatsApp customer
        $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone ?? '');
        $whatsappMessage = "Halo *".$customer->name."*,%0A%0A";
        $whatsappMessage .= "Kami mengingatkan bahwa Anda masih memiliki sisa utang sebesar *Rp ".number_format($remaining,0,',','.')."* di Toko Sembako Pak Sabar.%0A%0A";
        $whatsappMessage .= "Segera lunasi sebelum jatuh tempo ya. Terima kasih 🙏";
        $whatsappLink = $phoneNumber ? "https://wa.me/".$phoneNumber."?text=".$whatsappMessage : '#';
    @endphp

    <div class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition debitur-card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Info Pelanggan + Avatar -->
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-fill text-emerald-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-800">{{ $customer->name }}</p>
                    <p class="text-[11px] text-gray-400">NIK: {{ $customer->id }} / Invoice: {{ $receivable->salesTransaction->invoice_number ?? '-' }}</p>
                </div>
            </div>

            <!-- Nominal Utang & Progress Bar -->
            <div class="flex-1 min-w-[150px]">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Sisa Utang</span>
                    <span class="font-semibold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $percentPaid }}%"></div>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Terbayar {{ $percentPaid }}% dari Rp {{ number_format($totalDebt, 0, ',', '.') }}</p>
            </div>

            <!-- Status & Aksi -->
            <div class="flex items-center gap-3 shrink-0">
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    @if($statusColor == 'green') bg-green-100 text-green-700
                    @elseif($statusColor == 'yellow') bg-yellow-100 text-yellow-700
                    @else bg-red-100 text-red-700 @endif">
                    {{ $statusText }}
                </span>

                @if($remaining > 0 && $phoneNumber)
                <a href="{{ $whatsappLink }}" target="_blank"
                    class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                    <i class="bi bi-whatsapp"></i> Kirim Pengingat
                </a>
                @endif

                <a href="#" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium flex items-center gap-1">
                    Lihat Detail <i class="bi bi-chevron-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400 text-sm">
        <i class="bi bi-inbox text-2xl block mb-2"></i>
        Belum ada data utang.
    </div>
    @endforelse
</div>

    {{-- Pagination --}}
    @if ($receivables->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $receivables->appends(['status' => $status])->links() }}
    </div>
    @else
    <div class="text-center mt-5">
        <a href="#" class="inline-flex items-center gap-2 text-emerald-600 text-sm font-semibold hover:text-emerald-700 transition-colors">
            Lihat Semua Riwayat
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    @endif
</div>

{{-- Tips Penagihan Banner --}}
<div class="tips-banner rounded-2xl p-7 flex items-center gap-6">
    <div class="flex-1">
        <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3">
            <i class="bi bi-lightning-charge-fill text-xs"></i> Tips Penagihan
        </span>
        <h3 class="text-gray-900 text-xl font-extrabold leading-snug mb-2">
            Gunakan fitur "Kirim Pengingat" via WhatsApp<br>untuk mempermudah penagihan.
        </h3>
        <p class="text-gray-500 text-sm">
            Pelanggan yang diingatkan 3 hari sebelum jatuh tempo memiliki tingkat pelunasan 40% lebih tinggi.
        </p>
    </div>

    {{-- Phone Mockup --}}
    <div class="tips-phone rounded-2xl p-6 w-40 shrink-0 flex flex-col items-center justify-center gap-3">
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