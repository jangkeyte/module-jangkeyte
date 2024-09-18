<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminHomeController extends Controller
{
    public function index()
    {
        $total_companies = 1;
        $total_candidates = 1;
        $total_jobs = 1;
        //$job_statistic = visits('Modules\JobPortal\src\Models\Job');
        return view('Authetication::dashboard', compact('total_companies', 'total_candidates', 'total_jobs'));
    }

    public function dashboard()
    {
        $total_companies = 1;
        $total_candidates = 1;
        $total_jobs = 1;
        //$job_statistic = visits('Modules\JobPortal\src\Models\Job');
        //dd($job_statistic);
        return view('Authetication::dashboard', compact('total_companies', 'total_candidates', 'total_jobs'));
    }
}
