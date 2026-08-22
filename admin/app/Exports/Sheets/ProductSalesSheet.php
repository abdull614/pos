<?php

namespace App\Exports\Sheets;

use App\Models\SalesTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
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

class ProductSalesSheet implements FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle, ShouldAutoSize
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

    public function collection()
    {
        $query = SalesTransaction::with(['saleItems.product'])
            ->where('status', 'completed')
            ->orderBy('sold_at', 'asc');

        if (! $this->allTime) {
            $query->whereDate('sold_at', '>=', $this->dateFrom)
                  ->whereDate('sold_at', '<=', $this->dateTo);
        }

        return $query->get()->map(function ($trx) {
            $items = $trx->saleItems->map(function ($item) {
                $name = $item->product->name ?? 'Produk Dihapus';
                return $name . ' x' . $item->quantity;
            })->implode(', ');

            return [
                Carbon::parse($trx->sold_at)->format('d-m-Y H:i'),
                $trx->invoice_number ?? '#' . $trx->id,
                $items ?: '-',
                $trx->saleItems->count(),
                (float) $trx->total,
            ];
        });
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Invoice', 'Produk Dibeli', 'Jumlah Item', 'Total'];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0 "Rp"',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getColumnDimension('C')->setWidth(45);

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
                                'startColor' => ['rgb' => 'F1F8F2'],
                            ],
                        ]);
                    }
                }

                $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->freezePane('A2');
            },
        ];
    }

    public function title(): string
    {
        return 'Detail Produk';
    }
}