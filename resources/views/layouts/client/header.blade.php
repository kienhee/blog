<header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container position-relative d-flex align-items-center justify-content-between">

        <a href="/" class="logo d-flex align-items-center me-auto me-xl-0">
            <!-- Uncomment the line below if you also wish to use an image logo -->
            <!-- <img src="assets/img/logo.png" alt=""> -->
            <h1 class="sitename">ZenBlog</h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="/" class="active">Home</a></li>
                <li><a href="{{route("about")}}">About</a></li>
                <li><a href="single-post.html">Single Post</a></li>
                <li class="dropdown"><a href="#"><span>Categories</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                    <ul>
                        <li><a href="category.html">Category 1</a></li>
                        <li class="dropdown"><a href="#"><span>Deep Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            <ul>
                                <li><a href="#">Deep Dropdown 1</a></li>
                                <li><a href="#">Deep Dropdown 2</a></li>
                                <li><a href="#">Deep Dropdown 3</a></li>
                                <li><a href="#">Deep Dropdown 4</a></li>
                                <li><a href="#">Deep Dropdown 5</a></li>
                            </ul>
                        </li>
                        <li><a href="category.html">Category 2</a></li>
                        <li><a href="category.html">Category 3</a></li>
                        <li><a href="category.html">Category 4</a></li>
                    </ul>
                </li>
                <li><a href="{{route("contact")}}">Contact</a></li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>


        <div class="header-actions d-flex align-items-center" style="gap: 0.75rem;">
            <div class="header-social-links">
                <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
            </div>
            <div class="language-dropdown-custom">
                @php $lang = session('language', app()->getLocale()); @endphp
                <button class="lang-btn d-flex align-items-center" type="button" id="langDropdownBtn">
                    <img src="https://flagcdn.com/24x18/{{ $lang === 'en' ? 'gb' : 'vn' }}.png" alt="{{ strtoupper($lang) }}" class="lang-flag"> <span class="lang-label">{{ strtoupper($lang) }}</span>
                    <i class="bi bi-chevron-down ms-1"></i>
                </button>
                <div class="lang-dropdown-list" id="langDropdownList">
                    <button class="lang-option d-flex align-items-center{{ $lang === 'en' ? ' active' : '' }}" data-lang="en">
                        <img src="https://flagcdn.com/24x18/gb.png" alt="EN" class="lang-flag"> EN
                    </button>
                    <button class="lang-option d-flex align-items-center{{ $lang === 'vi' ? ' active' : '' }}" data-lang="vi">
                        <img src="https://flagcdn.com/24x18/vn.png" alt="VI" class="lang-flag"> VI
                    </button>
                </div>
                <form id="langForm" method="GET" action="{{ route('changeLanguage') }}" style="display:none;">
                    <input type="hidden" name="language" id="langInput">
                </form>
            </div>
            <button class="theme-switch" id="theme-switch" aria-label="Chuyển đổi chế độ sáng/tối">🌙 Dark</button>
        </div>

    </div>
</header>
