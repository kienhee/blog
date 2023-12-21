<!-- Navbar -->

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="d-flex">

                        <div class="flex-grow-1 me-3">
                            <span class="fw-semibold d-block">{{ Auth::user()->full_name }}</span>
                            <small class="text-muted  text-end d-block">{{ Auth::user()->group->name }}</small>
                        </div>
                        <div class="flex-shrink-0 ">
                            <div class="avatar avatar-online">
                                <img src="{{ Auth::user()->avatar ?? asset('images/avatar-default.png') }} "
                                    alt="avatar" class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover" />
                            </div>
                        </div>
                    </div>

                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item"
                            href="{{ url()->current() == route('dashboard.user.account-setting') ? 'javascript:void(0)' : route('dashboard.user.account-setting') }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ Auth::user()->avatar ?? asset('images/avatar-default.png') }}"
                                            alt="avatar" class="w-px-40 h-px-40 rounded-circle"
                                            style="object-fit: cover" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{ Auth::user()->full_name }}</span>
                                    <small class="text-muted">{{ Auth::user()->group->name }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="{{ url()->current() == route('dashboard.user.account-setting') ? 'javascript:void(0)' : route('dashboard.user.account-setting') }}">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Hồ sơ</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="{{ url()->current() == route('dashboard.user.change-password') ? 'javascript:void(0)' : route('dashboard.user.change-password') }}">
                            <i class='bx bx-lock-alt me-2'></i>
                            <span class="align-middle">Đổi mật khẩu</span>
                        </a>
                    </li>

                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <form action="{{ route('auth.logout') }}" method="POST" class="dropdown-item">
                            @csrf
                            @method('delete')

                            <button class="align-middle btn p-0"><i class="bx bx-power-off me-2"></i>Đăng xuất</button>
                        </form>

                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

<!-- / Navbar -->
