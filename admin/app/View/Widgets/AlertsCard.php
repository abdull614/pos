<?php

namespace App\View\Widgets;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AlertsCard extends Component
{
    public $lowStockProducts;
    public $debts;

    public function __construct($lowStockProducts = [], $debts = [])
    {
        $this->lowStockProducts = $lowStockProducts;
        $this->debts = $debts;
    }

    public function render()
    {
        return view('widgets.alerts-card');
    }
}
