<?php

namespace JangKeyte\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface ExternalAuthenticatorInterface
{
    /**
     * Attempt authentication against an external identity provider (e.g. KTX SSO API).
     * Return the provisioned/synced Authenticatable user on success, or null to fall back to local DB auth.
     */
    public function attempt(Request $request, array $credentials): ?Authenticatable;
}
