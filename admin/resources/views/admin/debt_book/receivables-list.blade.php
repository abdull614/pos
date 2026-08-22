@php
    $currentRouteName = request()->route()->getName();
@endphp

<x-widget::list-card
    :total="$receivables->total()"
    search-placeholder="Cari nama debitur atau invoice..."
    empty-text="Belum ada data utang"
    empty-icon="bi-inbox"
    :is-empty="$receivables->isEmpty()"
>
    <x-slot:filters>
        {{--
            FIX: sebelumnya label teks penuh ("Semua", "Belum Bayar", dst)
            selalu tampil, sering bikin baris filter meluber/wrap tidak
            rapi di layar kecil. Sekarang tiap tab pakai ikon + teks, tapi
            teksnya disembunyikan (hidden) di bawah breakpoint sm — jadi di
            HP cuma ikon yang tampil (hemat ruang), teks muncul lagi mulai
            layar sm ke atas.
        --}}
        <a href="{{ route($currentRouteName, ['status' => 'all']) }}"
           title="Semua"
           class="filter-tab flex items-center gap-1.5 text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-md {{ $status === 'all' ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap"></i>
            <span class="hidden sm:inline">Semua</span>
        </a>
        <a href="{{ route($currentRouteName, ['status' => 'unpaid']) }}"
           title="Belum Bayar"
           class="filter-tab flex items-center gap-1.5 text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-md {{ $status === 'unpaid' ? 'active' : '' }}">
            <i class="bi bi-exclamation-circle"></i>
            <span class="hidden sm:inline">Belum Bayar</span>
        </a>
        <a href="{{ route($currentRouteName, ['status' => 'partial']) }}"
           title="Sebagian"
           class="filter-tab flex items-center gap-1.5 text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-md {{ $status === 'partial' ? 'active' : '' }}">
            <i class="bi bi-hourglass-split"></i>
            <span class="hidden sm:inline">Sebagian</span>
        </a>
        <a href="{{ route($currentRouteName, ['status' => 'paid']) }}"
           title="Lunas"
           class="filter-tab flex items-center gap-1.5 text-xs font-semibold px-2.5 sm:px-3 py-1.5 rounded-md {{ $status === 'paid' ? 'active' : '' }}">
            <i class="bi bi-check-circle"></i>
            <span class="hidden sm:inline">Lunas</span>
        </a>
    </x-slot:filters>

    <x-slot:head>
        <th class="text-left px-3 sm:px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Debitur</th>
    </x-slot:head>

    {{-- $receivables sekarang berisi objek gabungan PER CUSTOMER, bukan
         per catatan utang. Kalau seorang customer punya beberapa catatan
         utang (kasbon/transaksi terpisah), semuanya sudah digabung jadi
         satu $group di controller. --}}
    @foreach ($receivables as $group)
        @php
            $customer = $group->customer;
            $totalDebt = $group->total_amount;
            $remaining = $group->total_remaining;
            $percentPaid = $group->percent_paid;
            $itemCount = $group->receivables->count();

            switch ($group->status) {
                case 'paid':
                    $statusText = 'Lunas';
                    $badgeClass = 'bg-green-100 text-green-700';
                    break;
                case 'partial':
                    $statusText = 'Cicilan Aktif';
                    $badgeClass = 'bg-yellow-100 text-yellow-700';
                    break;
                default: // unpaid
                    $statusText = 'Belum Dibayar';
                    $badgeClass = 'bg-red-100 text-red-700';
            }

            // --- Status tanggal jatuh tempo (pakai jatuh tempo TERDEKAT
            // di antara semua catatan utang customer ini yang masih ada
            // sisa). Bandingkan tanggal saja (tanpa jam) supaya tidak
            // muncul angka pecahan seperti "0.42 hari".
            $dueDate = $group->due_date;
            $today = \Carbon\Carbon::today();
            $dueDateOnly = $dueDate ? $dueDate->copy()->startOfDay() : null;

            $isDueToday = $remaining > 0 && $dueDateOnly && $dueDateOnly->equalTo($today);
            $isOverdue = $remaining > 0 && $dueDateOnly && $dueDateOnly->lt($today);
            $overdueDays = $isOverdue ? $dueDateOnly->diffInDays($today) : 0;

            $dueDateText = $dueDate ? $dueDate->translatedFormat('d M Y') : '-';

            // Daftar invoice/keterangan dari semua catatan utang yang
            // digabung, dipakai untuk sub-teks di bawah nama debitur.
            $invoiceLabels = $group->receivables
                ->map(fn ($r) => $r->salesTransaction->invoice_number ?? null)
                ->filter()
                ->values();

            $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone ?? '');
            $whatsappMessage = "Halo *".$customer->name."*,%0A%0A";
            $whatsappMessage .= "Kami mengingatkan bahwa Anda masih memiliki sisa utang sebesar *Rp ".number_format($remaining,0,',','.')."* di Toko Sembako Pak Sabar.%0A%0A";
            $whatsappMessage .= "Segera lunasi sebelum jatuh tempo ya. Terima kasih 🙏";
            $whatsappLink = $phoneNumber ? "https://wa.me/".$phoneNumber."?text=".$whatsappMessage : '#';
        @endphp

        <tr class="hover:bg-gray-50/70 transition align-top">
            <td class="px-3 sm:px-5 py-3">
                {{-- FIX: padding kartu diperkecil di mobile (p-4 -> p-5 mulai sm)
                     supaya lebih hemat ruang di layar sempit. --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-4 sm:p-5 hover:shadow-md transition debitur-card">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        {{-- FIX: min-w-0 supaya flex child ini boleh menyusut
                             di bawah lebar kontennya sendiri — tanpa ini,
                             teks nama/invoice panjang bisa mendorong layout
                             melebar/overflow di layar sempit. Nama & invoice
                             juga di-truncate agar tidak membungkus 2 baris
                             yang bikin kartu jadi tinggi tidak konsisten. --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                <i class="bi bi-person-fill text-emerald-600 text-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate">{{ $customer->name }}</p>
                                <p class="text-[11px] text-gray-400 truncate">
                                    NIK: {{ $customer->id }}
                                    @if($invoiceLabels->isNotEmpty())
                                        / Invoice: {{ $invoiceLabels->implode(', ') }}
                                    @endif
                                    @if($itemCount > 1)
                                        <span class="text-emerald-600 font-medium">&middot; {{ $itemCount }} catatan utang</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0 md:min-w-37.5">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Sisa Utang</span>
                                <span class="font-semibold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $percentPaid }}%"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Terbayar {{ $percentPaid }}% dari Rp {{ number_format($totalDebt, 0, ',', '.') }}</p>
                        </div>

                        <div class="flex flex-col items-start md:items-end gap-2 shrink-0">
                            {{-- FIX: flex-wrap supaya badge status + badge
                                 jatuh tempo tidak saling dorong keluar kartu
                                 di layar sempit, melainkan turun ke baris
                                 baru dengan rapi. --}}
                            <div class="flex items-center flex-wrap gap-2">
                              <span class="px-3 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">
                                  {{ $statusText }}
                              </span>
                              @if($remaining > 0)
                                  @if($isOverdue)
                                      <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 flex items-center gap-1">
                                          <i class="bi bi-exclamation-triangle-fill"></i>
                                          Terlambat {{ $overdueDays }} hari
                                      </span>
                                  @elseif($isDueToday)
                                      <span class="px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700 flex items-center gap-1">
                                          <i class="bi bi-exclamation-circle-fill"></i>
                                          Jatuh Tempo Hari Ini
                                      </span>
                                  @elseif($dueDate)
                                      <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                          Jatuh Tempo: {{ $dueDateText }}
                                      </span>
                                  @endif
                              @endif
                          </div>

                            {{-- FIX: flex-wrap + w-full di mobile supaya
                                 tombol "Kirim Pengingat" & "Lihat Detail"
                                 tidak berdempetan/terpotong di layar sempit. --}}
                            <div class="flex items-center flex-wrap gap-3 w-full md:w-auto">
                                @if($remaining > 0 && $phoneNumber)
                                <a href="{{ $whatsappLink }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                    <i class="bi bi-whatsapp"></i> Kirim Pengingat
                                </a>
                                @endif
                                <a href="{{ route('debt-book.show', $customer->id) }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium flex items-center gap-1">
                                    Lihat Detail <i class="bi bi-chevron-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    @endforeach

    <x-slot:pagination>
        @if ($receivables->hasPages())
            {{ $receivables->links() }}
        @endif
    </x-slot:pagination>
</x-widget::list-card>