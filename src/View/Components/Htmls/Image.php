<?php

namespace Modules\Authetication\src\View\Components\Htmls;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Image extends Component
{
    /**
     * Create the component instance.
     */
    public function __construct(
        public string $name,
        public ?string $object = null, 
        public ?string $class = null,      
        public ?string $alt = null,     
        public ?string $hidden = null,
    ) {}
 
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('JangKeyte::components.htmls.image');
    }
}
