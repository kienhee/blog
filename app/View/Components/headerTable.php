<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class headerTable extends Component
{
    /**
     * Create a new component instance.
     */
    public $tableName;
    public $link;
    public $linkName;
    public function __construct($tableName, $link, $linkName)
    {
        $this->tableName = $tableName;
        $this->link = $link;
        $this->linkName = $linkName;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.header-table');
    }
}
