<?php

namespace JangKeyte\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JangKeyte\Contracts\ExternalAuthenticatorInterface;

class NullExternalAuthenticator implements ExternalAuthenticatorInterface
{
    public function attempt(Request $request, array $credentials): ?Authenticatable
    {
        return null;
    }
}
