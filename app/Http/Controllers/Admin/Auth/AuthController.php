<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginView()
    {
        // dd(Hash::make('123456789'));
        if (Auth::check()) {
            return  back();
        }
        $layout = 'auth';
        return view('admin.auth.Login', compact('layout'));
    }

    public function login(Request $request)
    {

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            "email.required" => "Vui lòng nhập email",
            "email.email" => "Vui lòng nhập đúng định dạng",
            "password.required" => "Vui lòng nhập mật khẩu",

        ]);
        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard.index');
        }

        return back()->withErrors([
            'email' => 'Tài khoản hoặc mật khẩu chưa chính xác!',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('auth.loginView');
    }
}
