<?php

namespace JangKeyte\Contracts;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

interface DashboardHandlerInterface
{
    public function render(Request $request): View;
}
