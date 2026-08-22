<?php

namespace App\View\Widgets;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RecentTransactionsCard extends Component
{
    public $transactions;

    public function __construct($transactions = [])
    {
        $this->transactions = $transactions;
    }

    public function render()
    {
        return view('widgets.recent-transactions-card');
    }
}
