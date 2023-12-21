<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class breadcrumb extends Component
{
    /**
     * Create a new component instance.
     */
    public $parentName;
    public $parentLink;
    public $childrenName;
    public function __construct($parentName, $parentLink, $childrenName)
    {
        $this->parentName = $parentName;
        $this->parentLink = $parentLink;
        $this->childrenName = $childrenName;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.breadcrumb');
    }
}
