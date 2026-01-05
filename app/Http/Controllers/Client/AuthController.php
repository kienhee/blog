<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\ForgotPasswordRequest;
use App\Http\Requests\Client\Auth\LoginRequest;
use App\Http\Requests\Client\Auth\RegisterRequest;
use App\Http\Requests\Client\Auth\ResetPasswordRequest;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    protected $authRepository;
    protected $userRepository;

    public function __construct(AuthRepository $authRepository, UserRepository $userRepository)
    {
        $this->authRepository = $authRepository;
        $this->userRepository = $userRepository;
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        // Lưu URL redirect vào session nếu có (chỉ chấp nhận URL cùng domain để tránh open redirect)
        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            
            // Chỉ lưu nếu URL là relative hoặc cùng domain với app
            if ($redirectHost === null || $redirectHost === $appHost) {
                $request->session()->put('url.intended', $redirectUrl);
            }
        }

        $seoModel = new SEOData(
            title: 'Đăng nhập',
            description: 'Đăng nhập vào tài khoản của bạn để truy cập các tính năng độc quyền và quản lý thông tin cá nhân.',
            url: route('client.auth.login', [], false),
            type: 'website',
            robots: 'noindex, follow',
        );

        return view('client.pages.auth.login', compact('seoModel'));
    }

    public function loginHandle(LoginRequest $request)
    {
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            $user->load('roles');

            return redirect()
                ->intended($this->getRedirectUrlByRole($user))
                ->with('success', 'Đăng nhập thành công!');
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

        $seoModel = new SEOData(
            title: 'Đăng ký',
            description: 'Tạo tài khoản mới để tham gia cộng đồng, nhận thông báo và truy cập các tính năng độc quyền.',
            url: route('client.auth.register', [], false),
            type: 'website',
            robots: 'noindex, follow',
        );

        return view('client.pages.auth.register', compact('seoModel'));
    }

    public function registerHandle(RegisterRequest $request)
    {
        try {
            $user = $this->userRepository->createUser([
                'full_name' => $request->validated()['full_name'],
                'email' => $request->validated()['email'],
                'phone' => $request->validated()['phone'] ?? null,
                'password' => $request->validated()['password'],
            ]);

            // Gán role guest cho user mới đăng ký
            $guestRole = Role::where('name', 'guest')->first();
            if ($guestRole && $user) {
                $user->assignRole($guestRole);
            }

            // Tự động đăng nhập sau khi đăng ký
            $credentials = [
                'email' => $request->validated()['email'],
                'password' => $request->validated()['password'],
            ];

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                return redirect()
                    ->route('client.home')
                    ->with('success', 'Đăng ký thành công! Chào mừng bạn đến với '.env('APP_NAME', 'Blog'));
            }

            return redirect()
                ->route('client.auth.login')
                ->with('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['email' => 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại.'])
                ->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('client.home')
            ->with('success', 'Đăng xuất thành công!');
    }

    public function showForgotPasswordForm()
    {
        $seoModel = new SEOData(
            title: 'Quên mật khẩu',
            description: 'Khôi phục mật khẩu của bạn bằng cách nhập email đã đăng ký. Chúng tôi sẽ gửi link đặt lại mật khẩu đến email của bạn.',
            url: route('client.auth.forgot-password', [], false),
            type: 'website',
            robots: 'noindex, follow',
        );

        return view('client.pages.auth.forgot-password', compact('seoModel'));
    }

    public function sendPasswordResetLink(ForgotPasswordRequest $request)
    {
        $result = $this->authRepository->sendPasswordResetEmail($request->validated()['email']);

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
            return redirect()
                ->route('client.auth.forgot-password')
                ->withErrors(['email' => $tokenValidation['message']]);
        }

        $seoModel = new SEOData(
            title: 'Đặt lại mật khẩu',
            description: 'Đặt lại mật khẩu mới cho tài khoản của bạn. Vui lòng nhập mật khẩu mới và xác nhận.',
            url: route('client.auth.reset-password', [], false),
            type: 'website',
            robots: 'noindex, follow',
        );

        return view('client.pages.auth.reset-password', [
            'token' => $tokenValidation['token'],
            'email' => $tokenValidation['email'],
            'seoModel' => $seoModel,
        ]);
    }

    public function updatePassword(ResetPasswordRequest $request)
    {
        $result = $this->authRepository->resetPassword(
            $request->validated()['token'],
            $request->validated()['email'],
            $request->validated()['password']
        );

        if ($result['success']) {
            return redirect()
                ->route('client.auth.login')
                ->with('status', $result['message']);
        }

        return redirect()
            ->route('client.auth.forgot-password')
            ->withErrors(['email' => $result['message']]);
    }

    /**
     * Redirect theo role của user
     */
    private function redirectByRole($user)
    {
        if ($user->hasRole('guest')) {
            return redirect()->route('client.home');
        }

        return redirect()->route('admin.dashboard.analytics');
    }

    /**
     * Lấy URL redirect theo role
     */
    private function getRedirectUrlByRole($user)
    {
        if ($user->hasRole('guest')) {
            return route('client.home');
        }

        return route('admin.dashboard.analytics');
    }
}
