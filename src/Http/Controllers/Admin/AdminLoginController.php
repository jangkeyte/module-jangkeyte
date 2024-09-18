<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Mail\Websitemail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminLoginController extends Controller
{
    public function index()
    {
        return view('Authetication::admin.login');
    }
    
    public function forget_password()
    {
        return view('Authetication::admin.forget_password');
    }
     
    public function forget_password_submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $admin_data = User::where('email', $request->email)->first();
        if(!$admin_data) {
            return redirect()->back()->with('error', __('Email address not found.'));
        }

        $token = hash('sha256', time());

        $admin_data->token = $token;
        $admin_data->update();

        $reset_link = url('admin/reset-password/' . $token . '/' . $request->email);
        $subject = 'Reset Password';
        $message = 'Please click on the following link: <br>';
        $message .= '<a href="' . $reset_link . '">Click here</a>';

        Mail::to($request->email)->send(new Websitemail($subject, $message));

        return redirect()->route('admin_login')->with('success', __('Please check your email and follow the steps there.'));
    }
    
    public function login_submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credential = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if(Auth::guard('admin')->attempt($credential)){
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('admin_login')->with('error', __('Information is not correct.'));
        }
    }
    
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin_login');
    }
    
    public function reset_password($token, $email)
    {
        $admin_data = User::where('token', $token)->where('email', $email)->first();

        if(!$admin_data) {
            return redirect()->route('admin_login');
        }

        return view('Authetication::admin.reset_password', compact('token', 'email'));
    }

    public function reset_password_submit(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'retype_password' => 'required|same:password'
        ]);

        $admin_data = User::where('token', $request->token)->where('email', $request->email)->first();

        $admin_data->password = Hash::make($request->password);
        $admin_data->token = '';
        $admin_data->update();

        return redirect()->route('admin_login')->with('success', __('Password is reset successfully.'));
    }
}
