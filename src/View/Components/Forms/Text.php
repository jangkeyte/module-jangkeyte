<?php

namespace JangKeyte\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Text extends Component
{
    public function __construct(
        public string $name = '',
        public ?string $label = null,
        public mixed $value = null,
        public ?string $placeholder = null,
        public ?string $disabled = null,
        public ?string $required = null,
        public ?string $class = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('JangKeyte::components.forms.text');
    }
}
