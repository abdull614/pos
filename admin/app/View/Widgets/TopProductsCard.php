<?php

namespace App\View\Widgets;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TopProductsCard extends Component
{
    public $products;
    public $title;

    public function __construct($products = [], $title = 'Produk Terlaris')
    {
        $this->products = $products;
        $this->title = $title;
    }

    public function render()
    {
        return view('widgets.top-products-card');
    }
}
