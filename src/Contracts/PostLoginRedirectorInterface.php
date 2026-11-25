<?php

namespace JangKeyte\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface PostLoginRedirectorInterface
{
    public function redirect(Request $request, Authenticatable $user): RedirectResponse;
}
