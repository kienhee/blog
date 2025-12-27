<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->load('roles');
            
            // Kiểm tra role và redirect tương ứng
            if ($user->hasRole('guest')) {
                return redirect()->route('client.home');
            } else {
                return redirect()->route('admin.dashboard.analytics');
            }
        }

        return view('client.pages.auth.login');
    }

    public function loginHandle(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập email hoặc số điện thoại',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ]);

        $remember = $request->has('remember');
        $email = $request->input('email');

        // Đăng nhập bằng email
        $credentials = ['email' => $email, 'password' => $request->input('password')];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            $user->load('roles');
            
            // Kiểm tra role và redirect tương ứng
            if ($user->hasRole('guest')) {
                // Role guest -> redirect về client
                return redirect()->intended(route('client.home'))->with('success', 'Đăng nhập thành công!');
            } else {
                // Các role khác (superadmin, editor, ...) -> redirect về admin
                return redirect()->intended(route('admin.dashboard.analytics'))->with('success', 'Đăng nhập thành công!');
            }
        }

        return back()->withErrors([
            'email' => 'Tài khoản hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('client.home');
        }

        return view('client.pages.auth.register');
    }

    public function registerHandle(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên',
            'full_name.min' => 'Họ và tên phải có ít nhất :min ký tự',
            'full_name.max' => 'Họ và tên không được vượt quá :max ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.max' => 'Email không được vượt quá :max ký tự',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự',
            'phone.regex' => 'Số điện thoại chỉ được chứa số',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự',
            'password.max' => 'Mật khẩu không được vượt quá :max ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        try {
            $userRepository = app(UserRepository::class);
            $user = $userRepository->createUser([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
            ]);

            // Gán role guest cho user mới đăng ký
            $guestRole = Role::where('name', 'guest')->first();
            if ($guestRole && $user) {
                $user->assignRole($guestRole);
            }

            // Tự động đăng nhập sau khi đăng ký
            $credentials = ['email' => $validated['email'], 'password' => $validated['password']];
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                return redirect()->route('client.home')->with('success', 'Đăng ký thành công! Chào mừng bạn đến với '.env('APP_NAME', 'Blog'));
            }

            return redirect()->route('client.auth.login')->with('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại.'])->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.home')->with('success', 'Đăng xuất thành công!');
    }

    public function showForgotPasswordForm()
    {
        return view('client.pages.auth.forgot-password');
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
            return redirect()->route('client.auth.forgot-password')
                ->withErrors(['email' => $tokenValidation['message']]);
        }

        return view('client.pages.auth.reset-password', [
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
            return redirect()->route('client.auth.login')
                ->with('status', $result['message']);
        }

        return redirect()->route('client.auth.forgot-password')
            ->withErrors(['email' => $result['message']]);
    }
}
