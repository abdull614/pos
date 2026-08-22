<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex-1 min-h-0 flex flex-col" id="list-card-wrapper">

    <!-- Top Bar -->
    {{--
        FIX: sebelumnya "flex items-center justify-between" memaksa search
        box + filter + total jadi satu baris. Di layar sempit ini bikin
        filter-tab kepotong/menumpuk vertikal aneh (seperti di screenshot).
        Sekarang: flex-col di mobile (search di baris atas, filter+total di
        baris bawah), balik jadi satu baris (sm:flex-row) mulai layar sm.
    --}}
    <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="relative w-full sm:w-auto">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input
                type="text"
                id="search-input"
                value="{{ request('search') }}"
                placeholder="{{ $searchPlaceholder }}"
                autocomplete="off"
                class="pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent w-full sm:w-56">
        </div>
        {{--
            Baris kedua: filter di kiri (boleh scroll horizontal kalau
            tab-nya banyak/sempit), jumlah data di kanan. justify-between
            supaya total tetap menempel di ujung kanan walau filter cuma
            sedikit.
        --}}
        <div class="flex items-center justify-between gap-3">
            <div id="list-filters" class="flex items-center gap-1 overflow-x-auto">
                {{ $filters ?? '' }}
            </div>
            <span class="text-xs text-gray-400 font-medium shrink-0" id="list-total">{{ $total }} data</span>
        </div>
    </div>

    <div class="overflow-x-auto overflow-y-auto flex-1 min-h-0">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
                <tr class="bg-gray-50 border-b border-gray-100">
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50" id="list-tbody">
                @if($isEmpty)
                    <tr>
                        <td colspan="99" class="text-center py-16">
                            <i class="bi {{ $emptyIcon }} text-gray-300 text-4xl block mb-3"></i>
                            <p class="text-gray-400 font-medium">{{ $emptyText }}</p>
                            {{ $emptyAction ?? '' }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @isset($pagination)
        <div class="px-5 py-4 border-t border-gray-100" id="list-pagination">
            {{ $pagination }}
        </div>
    @endisset

</div>

<script>
(function () {
    const wrapper = document.getElementById('list-card-wrapper');
    const input = document.getElementById('search-input');
    if (!wrapper) return;

    let debounceTimer;

    async function fetchAndSwap(url) {
        const res = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const map = ['list-tbody', 'list-total', 'list-filters', 'list-pagination'];
        map.forEach(function (id) {
            const newEl = doc.getElementById(id);
            const currentEl = document.getElementById(id);
            if (newEl && currentEl) {
                currentEl.innerHTML = newEl.innerHTML;
            } else if (!newEl && currentEl && id === 'list-pagination') {
                // hasil baru tidak punya pagination (mis. data sedikit), kosongkan
                currentEl.innerHTML = '';
            }
        });

        // update URL browser tanpa reload, supaya refresh/back tetap sinkron
        window.history.replaceState({}, '', url.toString());
    }

    function runSearch(page = null) {
        const url = new URL(window.location.href);
        if (input && input.value) {
            url.searchParams.set('search', input.value);
        } else {
            url.searchParams.delete('search');
        }
        if (page) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        fetchAndSwap(url);
    }

    if (input) {
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                runSearch();
            }, 400);
        });
    }

    // Delegasi klik untuk filter tab & pagination.
    // Pakai delegation di wrapper (bukan di elemen yang di-innerHTML-replace)
    // supaya listener tidak perlu dipasang ulang tiap kali fetch selesai.
    wrapper.addEventListener('click', function (e) {
        const link = e.target.closest('#list-filters a, #list-pagination a');
        if (!link) return;

        e.preventDefault();

        const url = new URL(link.href, window.location.origin);

        // pertahankan kata kunci pencarian yang sedang aktif saat pindah tab/halaman,
        // kecuali link tujuan memang sudah bawa parameter search sendiri
        if (input && input.value && !url.searchParams.has('search')) {
            url.searchParams.set('search', input.value);
        }

        fetchAndSwap(url);
    });
})();
</script>