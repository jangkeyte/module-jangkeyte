<?php

namespace JangKeyte\View\Components\Htmls;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Image extends Component
{
    public function __construct(
        public ?string $name = null,
        public ?string $object = null,
        public ?string $class = null,
        public ?string $alt = null,
        public ?string $hidden = null,
        public ?string $style = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('JangKeyte::components.htmls.image');
    }
}
