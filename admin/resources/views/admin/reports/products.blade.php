@extends('admin.layouts.app')

@section('title', 'Laporan Produk')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box"></i> Laporan Penjualan Produk</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('reports.products') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Data Penjualan Produk</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Terjual</th>
                        <th>Revenue</th>
                        <th>Rata-rata Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->total_sold }}</td>
                            <td>Rp {{ number_format($product->revenue, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($product->revenue / $product->total_sold, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Belum ada data penjualan produk
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
</div>
@endsection
