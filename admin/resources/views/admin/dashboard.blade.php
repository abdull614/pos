<x-layouts.layout title="Dashboard" pageTitle="Dashboard">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        {{-- 💰 TOTAL PENDAPATAN --}}
        <x-stat-card
            title="Total Pendapatan"
            :value="'Rp ' . number_format($todaysSales, 0, ',', '.')"
            icon="cash"
            color="emerald"
        />

        {{-- 💸 TOTAL PENGELUARAN (GANTI UTANG) --}}
        <x-stat-card
            title="Total Pengeluaran"
            :value="'Rp ' . number_format($todaysExpense, 0, ',', '.')"
            icon="arrow-trending-down"
            color="red"
        />

        {{-- 📈 LABA BERSIH BULAN --}}
        <x-stat-card
            title="Laba Bersih"
            :value="'Rp ' . number_format($netProfit, 0, ',', '.')"
            icon="chart-bar"
            color="emerald"
            :is-hero="true"
        />

    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        {{-- Kiri: Chart + Alerts --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            {{-- 📊 CHART --}}
            <x-chart-card
                title="Tren Pendapatan & Pengeluaran"
                :subtitle="$mode == 'monthly' ? 'Bulan ini' : '7 hari terakhir'"
                :data="$trendChart"
                :mode="$mode"
            />

            {{-- ⚠️ ALERT --}}
            <x-alerts-card
                :low-stock-products="$lowStockProducts"
                :debts="$customersWithDebt ?? []"
            />

        </div>

        {{-- Kanan: Produk Terlaris --}}
        <div class="lg:col-span-1">
            <x-top-products-card
                title="Produk Terlaris"
                :products="$topProducts"
            />
        </div>

    </div>

</x-layouts.layout>