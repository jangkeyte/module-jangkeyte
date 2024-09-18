<?php

namespace Modules\Authetication\src\Http\Controllers\User;
use Modules\JobPortal\src\Models\Admin;
use Modules\Authetication\src\Http\Controllers\Controller;
use Modules\Authetication\src\Http\Requests\User\LoginUserRequest;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Modules\Authetication\src\Repositories\User\UserRepositoryInterface;
/*
use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Http\Requests\Auth\LoginRequest;
use Modules\Authetication\src\Providers\RouteServiceProvider;
*/
class LoginController extends Controller
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * UserController constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function create()
    {
        if(!auth()->check()) {
            return view('Authetication::user.login');
        } else {
            return redirect('/');
        }        
    }
    
    public function store(LoginUserRequest $request): RedirectResponse
    {
        $credential = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if(auth()->guard('web')->attempt($credential)){
            if($request->user()->can('browse-booking')) {
                return redirect()->route('dashboard');
            } else {                
                return redirect('/');
            }
        } else {
            return redirect()->route('login')->with('error', __('Information is not correct.'));
        }
    }    
    
    public function destroy(Request $request): RedirectResponse
    {
        // Đăng xuất khỏi ứng dụng
        auth()->guard('auth')->logout();

        // Hủy session hiện tại
        $request->session()->invalidate();

        // Tạo token mới cho session
        $request->session()->regenerateToken();

        // Chuyển hướng đến trang chủ
        return redirect('/');
    }

    /*
    public function forget_password()
    {
        return view('Authetication::admin.forget_password');
    }
     
    public function forget_password_submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $admin_data = Admin::where('email', $request->email)->first();
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
    */
}