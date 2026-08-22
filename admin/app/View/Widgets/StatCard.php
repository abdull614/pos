<?php

namespace App\View\Widgets;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatCard extends Component
{
   public $title;
    public $value;
    public $icon;
    public $color;
    public $percentage;
    public $isHero;

    public function __construct($title, $value, $icon, $color = 'emerald', $percentage = null, $isHero = false)
    {
        $this->title = $title;
        $this->value = $value;
        $this->icon = $icon;
        $this->color = $color;
        $this->percentage = $percentage;
        $this->isHero = $isHero;
    }

    public function render()
    {
        return view('widgets.stat-card');
    }
}
