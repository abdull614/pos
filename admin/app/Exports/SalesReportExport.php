<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesReportExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            new Sheets\SalesSummarySheet($this->dateFrom, $this->dateTo, $this->allTime),
            new Sheets\ProductSalesSheet($this->dateFrom, $this->dateTo, $this->allTime),
            new Sheets\ProductLineItemsSheet($this->dateFrom, $this->dateTo, $this->allTime),
        ];
    }
}