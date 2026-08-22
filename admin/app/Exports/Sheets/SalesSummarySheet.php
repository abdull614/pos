<?php

namespace App\Exports\Sheets;

use App\Models\SalesTransaction;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Carbon;

class SalesSummarySheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithEvents, WithColumnFormatting, WithTitle, ShouldAutoSize
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
        $query = SalesTransaction::query()
            ->where('status', 'completed')
            ->orderBy('sold_at', 'asc');

        if (! $this->allTime) {
            $query->whereDate('sold_at', '>=', $this->dateFrom)
                  ->whereDate('sold_at', '<=', $this->dateTo);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Invoice', 'Metode Pembayaran', 'Total'];
    }

    public function map($trx): array
    {
        return [
            Carbon::parse($trx->sold_at)->format('d-m-Y H:i'),
            $trx->invoice_number ?? '#' . $trx->id,
            $trx->payment_type ?? '-',
            (float) $trx->total,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0 "Rp"',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
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

                $dataRange = "A2:{$lastColumn}{$lastRow}";

                // Border tipis untuk semua data
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);

                // Zebra stripe
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F6FA'],
                            ],
                        ]);
                    }
                }

                // Rata tengah kolom tanggal & metode pembayaran
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Freeze header row
                $sheet->freezePane('A2');
            },
        ];
    }

    public function title(): string
    {
        return 'Total Transaksi';
    }
}