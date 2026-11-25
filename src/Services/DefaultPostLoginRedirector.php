<?php

namespace JangKeyte\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JangKeyte\Contracts\PostLoginRedirectorInterface;

class DefaultPostLoginRedirector implements PostLoginRedirectorInterface
{
    public function redirect(Request $request, Authenticatable $user): RedirectResponse
    {
        if (method_exists($user, 'can') && $user->can('browse-dashboard')) {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->intended('/');
    }
}
