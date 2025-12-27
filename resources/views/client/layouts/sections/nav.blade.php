@php
    $menu = [
        [
            'title' => 'Trang chủ',
            'url' => route('client.home'),
            'isRoute' => true,
        ],
        [
            'title' => 'Bài viết',
            'url' => route('client.posts'),
            'isRoute' => true,
        ],
        [
            'title' => 'Tác giả',
            'url' => route('client.about'),
            'isRoute' => true,
        ],
        [
            'title' => 'Liên hệ',
            'url' => route('client.contact'),
            'isRoute' => true,
        ],
    ];
    function checkActiveMenu($url)
    {
        return url()->current() == $url ? 'active' : '';
    }

    // Build mobile menu structure
    $mobileBuilder = new CategoryMenuBuilder();
    $mobileBuilder->buildMobileMenu($categoryMenu ?? [], 0, 0);
    $mobilePanels = $mobileBuilder->panels;
    $mobileRootItems = $mobileBuilder->rootItems;
@endphp
<nav class="layout-navbar shadow-none py-0">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-4">
        {{-- Menu logo wrapper: Start --}}
        <div class="navbar-brand app-brand demo d-flex py-0 me-4">
            {{-- Mobile menu toggle: Start --}}
            <button class="navbar-toggler border-0 px-0 me-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="tf-icons bx bx-menu bx-sm align-middle"></i>
            </button>
            {{-- Mobile menu toggle: End --}}
            <a href="{{ route('client.home') }}" class="app-brand-link">
                @include('admin.components.logo')
            </a>
        </div>
        {{-- Menu logo wrapper: End --}}
        {{-- Menu wrapper: Start --}}
        <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
            <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl"
                type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="tf-icons bx bx-x bx-sm"></i>
            </button>
            <ul class="navbar-nav m-auto">
                @foreach ($menu as $index => $item)
                    <li class="nav-item">
                        <a class="nav-link fw-medium {{ checkActiveMenu($item['url']) }}"
                            href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                    </li>

                    {{-- Insert "Danh mục" menu after "Bài viết" (index 1) --}}
                    @if ($index === 1 && !empty($categoryMenu))
                        <li class="nav-item dropdown multi-level-dropdown">
                            <a href="javascript:void(0);" class="nav-link dropdown-toggle fw-medium"
                                id="multiLevelDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>Danh mục</span>
                            </a>
                            <div class="menu-stack-overlay d-lg-none"></div>
                            <div class="dropdown-menu dropdown-menu-wrapper">
                                {{-- Desktop: Regular dropdown menu --}}
                                <ul class="dropdown-menu dropdown-menu-start dropdown-menu-desktop"
                                    aria-labelledby="multiLevelDropdown">
                                    {!! renderCategoryDesktop($categoryMenu) !!}
                                </ul>

                                {{-- Mobile: Stack navigation menu --}}
                                <div class="dropdown-menu-mobile menu-stack-container">
                                    {{-- Level 0: Root menu --}}
                                    <div class="menu-stack-panel active" data-panel-id="0" data-level="0">
                                        <div class="menu-stack-header">
                                            <button class="menu-stack-back menu-stack-close" type="button"
                                                data-dismiss="dropdown">
                                                <i class="bx bx-x"></i>
                                            </button>
                                            <span class="menu-stack-title">Danh mục</span>
                                        </div>
                                        <ul class="menu-stack-list">
                                            {!! $mobileRootItems !!}
                                        </ul>
                                    </div>

                                    {{-- Dynamic panels for submenus --}}
                                    @foreach ($mobilePanels as $panel)
                                        {!! $panel !!}
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
                {{-- <li class="nav-item mega-dropdown">
                    <a href="javascript:void(0);"
                        class="nav-link dropdown-toggle navbar-ex-14-mega-dropdown mega-dropdown fw-medium"
                        aria-expanded="false" data-bs-toggle="mega-dropdown" data-trigger="hover">
                        <span data-i18n="Pages">Tham khảo</span>
                    </a>
                    <div class="dropdown-menu p-4">
                        <div class="row gy-4">
                            <div class="col-12 col-lg">
                                <div class="h6 d-flex align-items-center mb-2 mb-lg-3">
                                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="bx bx-grid-alt"></i></span>
                                    </div>
                                    <span class="ps-1">Other</span>
                                </div>
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link" href="pricing-page.html">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            <span data-i18n="Pricing">Pricing</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link" href="payment-page.html">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            <span data-i18n="Payment">Payment</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link" href="checkout-page.html">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            <span data-i18n="Checkout">Checkout</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link" href="help-center-landing.html">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            <span data-i18n="Help Center">Help Center</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-12 col-lg">
                                <div class="h6 d-flex align-items-center mb-2 mb-lg-3">
                                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="bx bx-lock-open"></i></span>
                                    </div>
                                    <span class="ps-1">Auth Demo</span>
                                </div>
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-login-basic.html" target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Login (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-login-cover.html" target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Login (Cover)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-register-basic.html" target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Register (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-register-cover.html" target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Register (Cover)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-register-multisteps.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Register (Multi-steps)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-forgot-password-basic.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Forgot Password (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-forgot-password-cover.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Forgot Password (Cover)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-reset-password-basic.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Reset Password (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-reset-password-cover.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Reset Password (Cover)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-12 col-lg">
                                <div class="h6 d-flex align-items-center mb-2 mb-lg-3">
                                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="bx bx-image-alt"></i></span>
                                    </div>
                                    <span class="ps-1">Other</span>
                                </div>
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/pages-misc-error.html" target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Error
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/pages-misc-under-maintenance.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Under Maintenance
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/pages-misc-comingsoon.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Coming Soon
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/pages-misc-not-authorized.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Not Authorized
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-verify-email-basic.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Verify Email (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-verify-email-cover.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Verify Email (Cover)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-two-steps-basic.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Two Steps (Basic)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mega-dropdown-link"
                                            href="../vertical-menu-template/auth-two-steps-cover.html"
                                            target="_blank">
                                            <i class="bx bx-radio-circle me-2"></i>
                                            Two Steps (Cover)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-4 d-none d-lg-block">
                                <div class="bg-body nav-img-col p-2">
                                    <img src="{{ asset_admin_url('assets/img/front-pages/misc/nav-item-col-img.png') }}"
                                        alt="nav item col image" class="w-100" />
                                </div>
                            </div>
                        </div>
                    </div>
                </li> --}}
            </ul>
        </div>
        <div class="landing-menu-overlay d-lg-none"></div>
        {{-- Menu wrapper: End --}}
        {{-- Toolbar: Start --}}
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            {{-- Style Switcher --}}
            <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="bx bx-sm"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                            <span class="align-middle"><i class="bx bx-sun me-2"></i>Light</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                            <span class="align-middle"><i class="bx bx-moon me-2"></i>Dark</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i class="bx bx-desktop me-2"></i>System</span>
                        </a>
                    </li>
                </ul>
            </li>
            {{-- / Style Switcher --}}

            {{-- navbar button: Start --}}
            @auth
                <li>
                    <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">
                        <span class="tf-icons bx bx-user me-md-1"></span>
                        <span class="d-none d-md-block">{{ Auth::user()->full_name ?? Auth::user()->email }}</span>
                    </a>
                </li>
                <li>
                    <form action="{{ route('client.auth.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <span class="tf-icons bx bx-log-out me-md-1"></span>
                            <span class="d-none d-md-block">Đăng xuất</span>
                        </button>
                    </form>
                </li>
            @else
                <li>
                    <a href="{{ route('client.auth.login') }}" class="btn btn-primary">
                        <span class="tf-icons bx bx-user me-md-1"></span>
                        <span class="d-none d-md-block">Đăng nhập</span>
                    </a>
                </li>
            @endauth
            {{-- navbar button: End --}}
        </ul>
        {{-- Toolbar: End --}}
    </div>
</nav>

@push('scripts')
    @vite('resources/js/client/navbar.js')
@endpush
