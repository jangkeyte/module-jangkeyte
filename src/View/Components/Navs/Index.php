<?php

namespace Modules\Authetication\src\View\Components\Navs;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Index extends Component
{
    /**
     * Create the component instance.
     */
    public function __construct(
        public ?string $title = null,        
    ) {}
 
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('JangKeyte::components.navs.index');
    }
}
