<?php

namespace Modules\Authetication\src\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Hidden extends Component
{
    /**
     * Create the component instance.
     */
    public function __construct(
        public string $name,
        public ?string $label,    
        public ?string $value = null,
        public ?string $default = null,
        public ?string $required = null,
        public ?bool $autoforcus = false,  
    ) {}
 
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('JangKeyte::components.forms.hidden');
    }
}
