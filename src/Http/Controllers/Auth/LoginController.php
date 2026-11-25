<?php

namespace JangKeyte\Http\Controllers\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use JangKeyte\Contracts\ExternalAuthenticatorInterface;
use JangKeyte\Contracts\PostLoginRedirectorInterface;
use JangKeyte\Http\Requests\Auth\LoginRequest;

class LoginController extends Controller
{
    public function __construct(
        protected ExternalAuthenticatorInterface $externalAuthenticator,
        protected PostLoginRedirectorInterface $redirector,
    ) {}

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirector->redirect(request(), Auth::user());
        }

        return view('JangKeyte::auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $guard = config('jangkeyte.auth.guard', 'web');
        $identifier = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // 1. Optional external SSO/API authenticator hook (bound by domain modules if needed)
        $externalUser = $this->externalAuthenticator->attempt($request, [
            'username' => $identifier,
            'password' => $password,
        ]);

        if ($externalUser !== null) {
            Auth::guard($guard)->login($externalUser, $remember);
            $request->session()->regenerate();

            return $this->redirector->redirect($request, $externalUser);
        }

        // 2. Local database authentication (supports username or email)
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (Auth::guard($guard)->attempt([$field => $identifier, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            return $this->redirector->redirect($request, Auth::guard($guard)->user());
        }

        return redirect()
            ->route('login')
            ->withInput($request->only('username'))
            ->with('error', __('Thông tin đăng nhập không chính xác.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $guard = config('jangkeyte.auth.guard', 'web');
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
