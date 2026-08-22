<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Carbon;

class ProductLineItemsSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnFormatting, WithTitle, ShouldAutoSize
{
    protected string $dateFrom;
    protected string $dateTo;
    protected bool $allTime;

    public function __construct(string $dateFrom, string $dateTo, bool $allTime = false)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
        $this->allTime  = $allTime;
    }

    public function query()
    {
        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->select(
                'sales_transactions.sold_at',
                'sales_transactions.invoice_number',
                'sales_transactions.id as trx_id',
                'products.name',
                'products.sku',
                'sale_items.quantity',
                'sale_items.subtotal'
            )
            ->where('sales_transactions.status', 'completed')
            ->orderBy('sales_transactions.sold_at', 'asc')
            ->orderBy('sale_items.id', 'asc');

        if (! $this->allTime) {
            $query->whereDate('sales_transactions.sold_at', '>=', $this->dateFrom)
                  ->whereDate('sales_transactions.sold_at', '<=', $this->dateTo);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Invoice', 'Nama Produk', 'SKU', 'Qty', 'Harga Satuan', 'Subtotal'];
    }

    public function map($row): array
    {
        $qty       = (int) $row->quantity;
        $subtotal  = (float) $row->subtotal;
        $unitPrice = $qty > 0 ? $subtotal / $qty : 0;

        return [
            Carbon::parse($row->sold_at)->format('d-m-Y H:i'),
            $row->invoice_number ?? '#' . $row->trx_id,
            $row->name,
            $row->sku,
            $qty,
            $unitPrice,
            $subtotal,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0 "Rp"',
            'G' => '#,##0 "Rp"',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B7791F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastRow    = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                if ($lastRow < 2) {
                    return;
                }

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);

                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FBF3E3'],
                            ],
                        ]);
                    }
                }

                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->freezePane('A2');
            },
        ];
    }

    public function title(): string
    {
        return 'Rincian Harga';
    }
}