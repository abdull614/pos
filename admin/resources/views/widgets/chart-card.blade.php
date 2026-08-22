@props([
    'title' => 'Chart',
    'subtitle' => '',
    'data' => [],
    'mode' => 'monthly'
])

@php
    // ==============================================
    // KONFIGURASI CANVAS SVG
    // ==============================================
    $width  = 700;
    $height = 220;
    $padX      = 10;
    $padTop    = 26; // ruang label nilai pendapatan di atas
    $padBottom = 26; // ruang label nilai pengeluaran di bawah

    $n = count($data);
    $chartWidth  = $width - $padX * 2;
    $chartHeight = $height - $padTop - $padBottom;
    $centerY = $padTop + $chartHeight / 2;
    $halfHeight = $chartHeight / 2;

    $incomeValues  = collect($data)->map(fn ($d) => (float) ($d['income'] ?? 0))->values();
    $expenseValues = collect($data)->map(fn ($d) => (float) ($d['expense'] ?? 0))->values();

    $maxVal = max($incomeValues->max() ?: 0, $expenseValues->max() ?: 0);
    $maxVal = $maxVal > 0 ? $maxVal : 1;

    $hasAnyData = $incomeValues->sum() > 0 || $expenseValues->sum() > 0;

    // Setiap periode punya "slot" x sendiri (bar chart, bukan garis kontinu),
    // karena data ini memang sudah dikelompokkan per hari/bulan (bucket),
    // bukan sampel yang mengalir terus seperti chart saham.
    $slotWidth = $n > 0 ? $chartWidth / $n : $chartWidth;
    $barWidth  = min($slotWidth * 0.5, 34);
    $radius    = min(6, $barWidth / 2);

    // Path bar dengan sudut membulat HANYA di ujung luar (atas untuk
    // pendapatan, bawah untuk pengeluaran), rata di sisi yang menyentuh
    // garis tengah (0) — biar terlihat seperti "pilar" naik/turun dari 0,
    // bukan kotak biasa.
    $roundedBarPath = function ($x0, $x1, $yBase, $yOuter, $roundTop) use ($radius) {
        $barHeight = abs($yOuter - $yBase);
        $r = min($radius, $barHeight / 2, ($x1 - $x0) / 2);
        if ($r < 0) $r = 0;

        if ($roundTop) {
            // Pendapatan: rata di bawah (yBase), membulat di atas (yOuter)
            return 'M' . round($x0, 2) . ',' . round($yBase, 2)
                . ' L' . round($x0, 2) . ',' . round($yOuter + $r, 2)
                . ' Q' . round($x0, 2) . ',' . round($yOuter, 2) . ' ' . round($x0 + $r, 2) . ',' . round($yOuter, 2)
                . ' L' . round($x1 - $r, 2) . ',' . round($yOuter, 2)
                . ' Q' . round($x1, 2) . ',' . round($yOuter, 2) . ' ' . round($x1, 2) . ',' . round($yOuter + $r, 2)
                . ' L' . round($x1, 2) . ',' . round($yBase, 2)
                . ' Z';
        }

        // Pengeluaran: rata di atas (yBase), membulat di bawah (yOuter)
        return 'M' . round($x0, 2) . ',' . round($yBase, 2)
            . ' L' . round($x0, 2) . ',' . round($yOuter - $r, 2)
            . ' Q' . round($x0, 2) . ',' . round($yOuter, 2) . ' ' . round($x0 + $r, 2) . ',' . round($yOuter, 2)
            . ' L' . round($x1 - $r, 2) . ',' . round($yOuter, 2)
            . ' Q' . round($x1, 2) . ',' . round($yOuter, 2) . ' ' . round($x1, 2) . ',' . round($yOuter - $r, 2)
            . ' L' . round($x1, 2) . ',' . round($yBase, 2)
            . ' Z';
    };

    $bars = [];
    foreach ($data as $i => $d) {
        $slotCenterX = $padX + $slotWidth * ($i + 0.5);
        $x0 = $slotCenterX - $barWidth / 2;
        $x1 = $slotCenterX + $barWidth / 2;

        $income  = (float) ($d['income'] ?? 0);
        $expense = (float) ($d['expense'] ?? 0);

        $incomeTopY   = $centerY - ($income / $maxVal) * $halfHeight;
        $expenseBotY  = $centerY + ($expense / $maxVal) * $halfHeight;

        $bars[$i] = [
            'x0' => $x0, 'x1' => $x1, 'cx' => $slotCenterX,
            'income' => $income, 'expense' => $expense,
            'incomeTopY' => $incomeTopY, 'expenseBotY' => $expenseBotY,
        ];
    }
@endphp

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-3 sm:pb-4 flex items-center justify-between border-b border-gray-50">
        <div>
            <h3 class="text-sm font-bold text-gray-900">{{ $title }}</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
        </div>

        <div class="flex gap-1">
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'weekly']) }}"
               class="px-2 sm:px-3 py-1.5 text-xs font-medium rounded-lg transition
               {{ $mode === 'weekly' ? 'bg-emerald-600 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                Mingguan
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'monthly']) }}"
               class="px-2 sm:px-3 py-1.5 text-xs font-medium rounded-lg transition
               {{ $mode === 'monthly' ? 'bg-emerald-600 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                Bulanan
            </a>
        </div>
    </div>

    {{-- Legend --}}
    <div class="px-4 sm:px-5 pt-3 flex items-center gap-4">
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-xs text-gray-500">Pendapatan</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
            <span class="text-xs text-gray-500">Pengeluaran</span>
        </div>
    </div>

    {{-- Chart --}}
    <div class="p-4 sm:p-5">
        @if($hasAnyData)
            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-48 sm:h-56" preserveAspectRatio="none">

                {{-- Garis tengah = titik 0, pembatas pendapatan (atas) & pengeluaran (bawah) --}}
                <line x1="{{ $padX }}" y1="{{ $centerY }}" x2="{{ $width - $padX }}" y2="{{ $centerY }}"
                      stroke="#e5e7eb" stroke-width="1" />

                @foreach($bars as $i => $b)
                    {{-- Bar pendapatan, naik ke atas dari garis tengah --}}
                    @if($b['income'] > 0)
                        <path d="{{ $roundedBarPath($b['x0'], $b['x1'], $centerY, $b['incomeTopY'], true) }}"
                              fill="#059669" fill-opacity="0.9" />
                    @endif

                    {{-- Bar pengeluaran, turun ke bawah dari garis tengah --}}
                    @if($b['expense'] > 0)
                        <path d="{{ $roundedBarPath($b['x0'], $b['x1'], $centerY, $b['expenseBotY'], false) }}"
                              fill="#dc2626" fill-opacity="0.85" />
                    @endif

                    {{-- Label nilai pendapatan di atas bar --}}
                    @if(!empty($data[$i]['income_display'] ?? null))
                        <text x="{{ $b['cx'] }}" y="{{ $b['incomeTopY'] - 8 }}"
                              font-size="9" font-weight="700" text-anchor="middle" fill="#047857">
                            {{ $data[$i]['income_display'] }}
                        </text>
                    @endif

                    {{-- Label nilai pengeluaran di bawah bar --}}
                    @if(!empty($data[$i]['expense_display'] ?? null))
                        <text x="{{ $b['cx'] }}" y="{{ $b['expenseBotY'] + 16 }}"
                              font-size="9" font-weight="700" text-anchor="middle" fill="#b91c1c">
                            {{ $data[$i]['expense_display'] }}
                        </text>
                    @endif
                @endforeach

            </svg>

            {{-- Label bulan/tanggal di bawah chart --}}
            <div class="flex gap-1.5 sm:gap-3 mt-2 px-1 sm:px-2">
                @foreach($data as $item)
                    <div class="flex-1 text-center">
                        <span class="text-[10px] text-gray-400">{{ $item['label'] ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Belum ada transaksi sama sekali di rentang ini --}}
            <div class="h-48 sm:h-56 flex flex-col items-center justify-center gap-2">
                <svg class="w-9 h-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3-3 3 2.5 4-5 5 4.5M4 19h16" />
                </svg>
                <span class="text-sm text-gray-400">Belum ada data</span>
            </div>
        @endif
    </div>

</div>