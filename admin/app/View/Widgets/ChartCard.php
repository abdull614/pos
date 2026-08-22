<?php

namespace App\View\Widgets;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ChartCard extends Component
{
    public $title;
    public $subtitle;
    public $data;
    public $mode;

    public function __construct(
        $title = 'Tren Penjualan',
        $subtitle = '',
        $data = [],
        $mode = 'monthly'
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->data = $data;
        $this->mode = $mode;
    }

    public function render()
    {
        return view('widgets.chart-card');
    }
}
