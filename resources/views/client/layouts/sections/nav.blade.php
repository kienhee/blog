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
@endphp
<nav class="layout-navbar shadow-none py-0">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-4">
        <!-- Menu logo wrapper: Start -->
        <div class="navbar-brand app-brand demo d-flex py-0 me-4">
            <!-- Mobile menu toggle: Start-->
            <button class="navbar-toggler border-0 px-0 me-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="tf-icons bx bx-menu bx-sm align-middle"></i>
            </button>
            <!-- Mobile menu toggle: End-->
            <a href="{{ route('client.home') }}" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink">
                        <defs>
                            <path
                                d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                                id="path-1"></path>
                            <path
                                d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                                id="path-3"></path>
                            <path
                                d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                                id="path-4"></path>
                            <path
                                d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                                id="path-5"></path>
                        </defs>
                        <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                <g id="Icon" transform="translate(27.000000, 15.000000)">
                                    <g id="Mask" transform="translate(0.000000, 8.000000)">
                                        <mask id="mask-2" fill="white">
                                            <use xlink:href="#path-1"></use>
                                        </mask>
                                        <use fill="#696cff" xlink:href="#path-1"></use>
                                        <g id="Path-3" mask="url(#mask-2)">
                                            <use fill="#696cff" xlink:href="#path-3"></use>
                                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                                        </g>
                                        <g id="Path-4" mask="url(#mask-2)">
                                            <use fill="#696cff" xlink:href="#path-4"></use>
                                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                                        </g>
                                    </g>
                                    <g id="Triangle"
                                        transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                                        <use fill="#696cff" xlink:href="#path-5"></use>
                                        <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>
                </span>
                <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">Sneat</span>
            </a>
        </div>
        <!-- Menu logo wrapper: End -->
        <!-- Menu wrapper: Start -->
        <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
            <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl"
                type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="tf-icons bx bx-x bx-sm"></i>
            </button>
            <ul class="navbar-nav m-auto">
                @foreach ($menu as $item)
                    <li class="nav-item">
                        <a class="nav-link fw-medium {{ checkActiveMenu($item['url']) }}"
                            href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                    </li>
                @endforeach
                <li class="nav-item dropdown multi-level-dropdown">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle fw-medium" id="multiLevelDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span>Danh mục</span>
                    </a>
                    <div class="menu-stack-overlay d-lg-none"></div>
                    <div class="dropdown-menu dropdown-menu-wrapper">
                        <!-- Desktop: Regular dropdown menu -->
                        <ul class="dropdown-menu dropdown-menu-start dropdown-menu-desktop"
                            aria-labelledby="multiLevelDropdown">
                            <li class="dropdown-item-parent">
                                <a class="dropdown-item dropdown-toggle" href="javascript:void(0);">
                                    <span>Mục 1</span>
                                    <i class="bx bx-chevron-right float-end"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-submenu">
                                    <li><a class="dropdown-item" href="#"><span>Mục 1.1</span></a></li>
                                    <li><a class="dropdown-item" href="#"><span>Mục 1.2</span></a></li>
                                    <li class="dropdown-item-parent">
                                        <a class="dropdown-item dropdown-toggle" href="javascript:void(0);">
                                            <span>Mục 1.3</span>
                                            <i class="bx bx-chevron-right float-end"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-submenu">
                                            <li><a class="dropdown-item" href="#"><span>Mục 1.3.1</span></a>
                                            </li>
                                            <li><a class="dropdown-item" href="#"><span>Mục 1.3.2</span></a>
                                            </li>
                                            <li><a class="dropdown-item" href="#"><span>Mục 1.3.3</span></a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="dropdown-item-parent">
                                <a class="dropdown-item dropdown-toggle" href="javascript:void(0);">
                                    <span>Mục 2</span>
                                    <i class="bx bx-chevron-right float-end"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-submenu">
                                    <li><a class="dropdown-item" href="#"><span>Mục 2.1</span></a></li>
                                    <li><a class="dropdown-item" href="#"><span>Mục 2.2</span></a></li>
                                    <li><a class="dropdown-item" href="#"><span>Mục 2.3</span></a></li>
                                </ul>
                            </li>
                            <li><a class="dropdown-item" href="#"><span>Mục 3</span></a></li>
                            <li><a class="dropdown-item" href="#"><span>Mục 4</span></a></li>
                        </ul>

                        <!-- Mobile: Stack navigation menu -->
                        <div class="dropdown-menu-mobile menu-stack-container">
                            <!-- Level 0: Root menu -->
                            <div class="menu-stack-panel active" data-panel-id="0" data-level="0">
                                <div class="menu-stack-header">
                                    <button class="menu-stack-back menu-stack-close" type="button"
                                        data-dismiss="dropdown">
                                        <i class="bx bx-x"></i>
                                    </button>
                                    <span class="menu-stack-title">Danh mục</span>
                                </div>
                                <ul class="menu-stack-list">
                                    <li class="menu-stack-item has-children" data-target="1">
                                        <span>Mục 1</span>
                                        <i class="bx bx-chevron-right"></i>
                                    </li>
                                    <li class="menu-stack-item has-children" data-target="2">
                                        <span>Mục 2</span>
                                        <i class="bx bx-chevron-right"></i>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 3</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 4</span></a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Level 1: Mục 1 submenu -->
                            <div class="menu-stack-panel" data-panel-id="1" data-level="1" data-parent="0">
                                <div class="menu-stack-header">
                                    <button class="menu-stack-back" type="button">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <span class="menu-stack-title">Mục 1</span>
                                </div>
                                <ul class="menu-stack-list">
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 1.1</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 1.2</span></a>
                                    </li>
                                    <li class="menu-stack-item has-children" data-target="3">
                                        <span>Mục 1.3</span>
                                        <i class="bx bx-chevron-right"></i>
                                    </li>
                                </ul>
                            </div>

                            <!-- Level 1: Mục 2 submenu -->
                            <div class="menu-stack-panel" data-panel-id="2" data-level="1" data-parent="0">
                                <div class="menu-stack-header">
                                    <button class="menu-stack-back" type="button">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <span class="menu-stack-title">Mục 2</span>
                                </div>
                                <ul class="menu-stack-list">
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 2.1</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 2.2</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 2.3</span></a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Level 2: Mục 1.3 submenu -->
                            <div class="menu-stack-panel" data-panel-id="3" data-level="2" data-parent="1">
                                <div class="menu-stack-header">
                                    <button class="menu-stack-back" type="button">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <span class="menu-stack-title">Mục 1.3</span>
                                </div>
                                <ul class="menu-stack-list">
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 1.3.1</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 1.3.2</span></a>
                                    </li>
                                    <li class="menu-stack-item">
                                        <a href="#"><span>Mục 1.3.3</span></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item mega-dropdown">
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
                </li>
            </ul>
        </div>
        <div class="landing-menu-overlay d-lg-none"></div>
        <!-- Menu wrapper: End -->
        <!-- Toolbar: Start -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Style Switcher -->
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
            <!-- / Style Switcher-->

            <!-- navbar button: Start -->
            <li>
                <a href="../vertical-menu-template/auth-login-cover.html" class="btn btn-primary"
                    target="_blank"><span class="tf-icons bx bx-user me-md-1"></span><span
                        class="d-none d-md-block">Login/Register</span></a>
            </li>
            <!-- navbar button: End -->
        </ul>
        <!-- Toolbar: End -->
    </div>
</nav>

@push('scripts')
    <script>
        (function() {
            const multiLevelDropdown = document.querySelector('.multi-level-dropdown');
            if (!multiLevelDropdown) return;

            const menuStackContainer = multiLevelDropdown.querySelector('.menu-stack-container');
            if (!menuStackContainer) return;

            function isMobile() {
                return window.innerWidth < 992;
            }

            // Menu stack navigation manager
            class MenuStackNavigation {
                constructor(container) {
                    this.container = container;
                    this.panels = Array.from(container.querySelectorAll('.menu-stack-panel'));
                    this.activePanel = container.querySelector('.menu-stack-panel.active');
                    this.history = [0]; // Track navigation history with panel indices
                    this.init();
                }

                init() {
                    // Set initial active panel
                    if (this.activePanel) {
                        const panelId = this.activePanel.dataset.panelId || '0';
                        this.history = [parseInt(panelId)];
                    }

                    // Handle menu item clicks
                    this.container.addEventListener('click', (e) => {
                        // Check if clicked on a link (not a menu item with children)
                        const link = e.target.closest('.menu-stack-item > a');
                        if (link) {
                            // Allow normal link navigation, don't prevent default
                            return;
                        }

                        // Handle menu items with children (submenu)
                        const menuItem = e.target.closest('.menu-stack-item.has-children');
                        if (menuItem) {
                            e.preventDefault();
                            e.stopPropagation();
                            const targetId = menuItem.dataset.target;
                            if (targetId) {
                                this.navigateTo(parseInt(targetId));
                            }
                            return;
                        }

                        // Handle back/close button
                        const backButton = e.target.closest('.menu-stack-back');
                        if (backButton) {
                            e.preventDefault();
                            e.stopPropagation();

                            // If it's close button on root menu, close dropdown
                            if (backButton.classList.contains('menu-stack-close')) {
                                this.closeDropdown();
                                return;
                            }

                            // Otherwise, go back in menu stack
                            this.goBack();
                        }
                    });
                }

                closeDropdown() {
                    const dropdownToggle = document.querySelector('#multiLevelDropdown');
                    if (dropdownToggle) {
                        // Try Bootstrap Dropdown API first
                        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                            const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                            if (bsDropdown) {
                                bsDropdown.hide();
                                return;
                            }
                        }

                        // Fallback: remove show class and aria-expanded
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                        const dropdownMenu = dropdownToggle.nextElementSibling;
                        if (dropdownMenu) {
                            dropdownMenu.classList.remove('show');
                        }

                        // Trigger click to close (Bootstrap will handle it)
                        dropdownToggle.click();
                    }
                }

                navigateTo(targetId) {
                    if (!isMobile()) return;

                    const targetPanel = this.panels.find(p => p.dataset.panelId === String(targetId));
                    if (!targetPanel) return;

                    const currentId = this.history[this.history.length - 1];
                    const currentPanel = this.panels.find(p => p.dataset.panelId === String(currentId));

                    // Update classes
                    if (currentPanel) {
                        currentPanel.classList.remove('active');
                        currentPanel.classList.add('prev');
                    }

                    targetPanel.classList.remove('prev');
                    targetPanel.classList.add('active');

                    // Update history
                    this.history.push(targetId);

                    // Remove prev class after animation
                    setTimeout(() => {
                        if (currentPanel) {
                            currentPanel.classList.remove('prev');
                        }
                    }, 300);
                }

                goBack() {
                    if (!isMobile()) return;
                    if (this.history.length <= 1) return;

                    // Remove current from history
                    const currentId = this.history.pop();
                    const previousId = this.history[this.history.length - 1];

                    const currentPanel = this.panels.find(p => p.dataset.panelId === String(currentId));
                    const previousPanel = this.panels.find(p => p.dataset.panelId === String(previousId));

                    if (currentPanel && previousPanel) {
                        // Slide out current
                        currentPanel.classList.remove('active');
                        currentPanel.style.transform = 'translateX(100%)';

                        // Slide in previous
                        previousPanel.classList.remove('prev');
                        previousPanel.classList.add('active');

                        // Reset transform after animation
                        setTimeout(() => {
                            currentPanel.style.transform = '';
                        }, 300);
                    }
                }

                reset() {
                    // Reset to root panel
                    this.panels.forEach(panel => {
                        panel.classList.remove('active', 'prev');
                        panel.style.transform = '';
                    });

                    const rootPanel = this.panels.find(p => p.dataset.panelId === '0');
                    if (rootPanel) {
                        rootPanel.classList.add('active');
                        this.history = [0];
                    }
                }
            }

            // Initialize menu stack navigation
            let menuStackNav = null;

            function initMenuStack() {
                if (isMobile() && menuStackContainer) {
                    if (!menuStackNav) {
                        menuStackNav = new MenuStackNavigation(menuStackContainer);
                    }
                } else if (menuStackNav) {
                    menuStackNav.reset();
                }
            }

            // Initialize on load
            initMenuStack();

            // Handle resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    initMenuStack();
                }, 150);
            });

            // Handle dropdown show/hide events
            const dropdownToggle = multiLevelDropdown.querySelector('[data-bs-toggle="dropdown"]');
            const menuStackOverlay = multiLevelDropdown.querySelector('.menu-stack-overlay');
            const dropdownWrapper = multiLevelDropdown.querySelector('.dropdown-menu-wrapper');

            if (dropdownToggle) {
                // Show overlay when dropdown opens
                dropdownToggle.addEventListener('shown.bs.dropdown', function() {
                    if (isMobile() && menuStackOverlay) {
                        menuStackOverlay.style.display = 'block';
                    }
                    if (menuStackNav && isMobile()) {
                        menuStackNav.reset();
                    }
                });

                // Hide overlay and reset menu stack when dropdown closes
                dropdownToggle.addEventListener('hidden.bs.dropdown', function() {
                    if (menuStackOverlay) {
                        menuStackOverlay.style.display = 'none';
                    }
                    if (menuStackNav) {
                        menuStackNav.reset();
                    }
                });
            }

            // Also handle click on overlay to close dropdown
            if (menuStackOverlay) {
                menuStackOverlay.addEventListener('click', function() {
                    if (dropdownToggle) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                            const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                            if (bsDropdown) {
                                bsDropdown.hide();
                            }
                        } else {
                            dropdownToggle.click();
                        }
                    }
                });
            }
        })();
    </script>
@endpush
