<?php

namespace Modules\JangKeyte\src\Http\Controllers;

use App\Http\Controllers\Controller;

class JangKeyteController extends Controller
{
    public function switchLanguage($language)
    {
        session()->put('website_language', $language);
        return redirect()->back();
    }
}