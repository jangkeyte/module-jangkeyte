<?php

namespace JangKeyte\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JangKeyte\Contracts\DashboardHandlerInterface;

class CoreController extends Controller
{
    public function switchLanguage(Request $request, string $language): RedirectResponse
    {
        $supported = config('jangkeyte.supported_locales', ['vi', 'en']);
        if (in_array($language, $supported, true)) {
            $request->session()->put('website_language', $language);
        }

        return redirect()->back();
    }

    public function dashboard(Request $request, DashboardHandlerInterface $handler): View
    {
        return $handler->render($request);
    }
}
