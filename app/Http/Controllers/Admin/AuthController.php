<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AuthRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(Request $request)
    {
        if (Auth::user()) {
            return redirect()->route('admin.dashboard.analytics');
        }

        return view('admin.modules.auth.login');
    }

    public function loginHandle(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $remember = $request->has('remember');
        $loginValue = $request->input('login');

        // Đăng nhập bằng email
        $credentials = ['email' => $loginValue, 'password' => $request->input('password')];

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            $user->load('roles');
            
            $request->session()->regenerate();
            
            // Kiểm tra role: nếu là guest thì chuyển hướng về trang home client
            if ($user->hasRole('guest')) {
                return redirect()->route('client.home')
                    ->with('info', 'Bạn đã đăng nhập thành công. Tài khoản của bạn không có quyền truy cập vào khu vực quản trị.');
            }

            // Ưu tiên đưa về trang người dùng định truy cập (intended URL) nhưng chỉ nội bộ /admin
            $intended = $request->session()->pull('url.intended');
            if ($intended && str_starts_with($intended, url('/admin'))) {
                return redirect()->to($intended);
            }

            return redirect()->route('admin.dashboard.analytics');
        }

        return back()->withErrors([
            'password' => 'Tài khoản hoặc mật khẩu không chính xác.',
        ])->onlyInput('password');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    public function showForgotPasswordForm()
    {
        return view('admin.modules.auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Vui lòng nhập email của bạn.',
            'email.email' => 'Email không đúng định dạng.',
            'email.exists' => 'Email này không tồn tại trong hệ thống.',
        ]);

        $result = $this->authRepository->sendPasswordResetEmail($request->email);

        if ($result['success']) {
            return back()->with('status', $result['message']);
        }

        return back()->withErrors(['email' => $result['message']])->withInput();
    }

    public function showResetPasswordForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        $tokenValidation = $this->authRepository->validateResetToken($token, $email);

        if (! $tokenValidation['valid']) {
            return redirect()->route('auth.forgot-password')
                ->withErrors(['email' => $tokenValidation['message']]);
        }

        return view('admin.modules.auth.reset-password', [
            'token' => $tokenValidation['token'],
            'email' => $tokenValidation['email'],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $result = $this->authRepository->resetPassword(
            $request->input('token'),
            $request->input('email'),
            $request->input('password')
        );

        if ($result['success']) {
            return redirect()->route('auth.login')
                ->with('status', $result['message']);
        }

        return redirect()->route('auth.forgot-password')
            ->withErrors(['email' => $result['message']]);
    }
}
