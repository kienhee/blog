<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KienTran🔥')</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('client') }}/assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('client') }}/assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('client') }}/assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="{{ asset('client') }}/assets/images/favicon/site.webmanifest">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="{{ asset('client') }}/assets/lib/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('client') }}/assets/lib/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('client') }}/assets/lib/owlcarousel/assets/owl.carousel.min.css">
    <script src="https://cdn.tiny.cloud/1/el9eht3oqsjlpvjkdu2mx5gh01fq5xie6zt09pq791iqfhej/tinymce/6/tinymce.min.js"
    referrerpolicy="origin"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="{{ asset('client') }}/assets/lib/prismjs/prism.css">
    <link rel="stylesheet" href="{{asset('client/assets/lib/lightbox/dist/css/lightbox.min.css')}}">
    <link rel="stylesheet" href="{{ asset('client') }}/assets/css/style.css">
    @yield('seo')


</head>

<body>
    <div id="preloader">
        <div id="loader"></div>
    </div>
    <header class="header py-2 shadow-sm mb-5">
        <div class="wrapper">
            <nav class="d-flex justify-content-between align-items-center">
                <a href="/" class="fw-medium text-uppercase d-flex align-items-center gap-2"> <img
                        src="{{ asset('client') }}/assets/images/favicon/favicon-32x32.png"
                        alt="Trần Trung Kiên"><span>kientran</span></a>
                <a href="{{ route('client.author') }}" class="fw-medium">Tác giả</a>
            </nav>
        </div>
    </header>
    <main class="main">
        @yield('content')

    </main>

    <script src="{{ asset('client') }}/assets/lib/jquery.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
    <script src="{{ asset('client') }}/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="{{ asset('client') }}/assets/lib/isotope/isotope-min.js"></script>
    <script src="{{ asset('client') }}/assets/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="{{ asset('client') }}/assets/lib/prismjs/prism.js"></script>
    <script src="{{ asset('admin/assets') }}/js/initTinymce.js"></script>
<script src="{{asset('client/assets/lib/lightbox/dist/js/lightbox.min.js')}}"></script>
    <script src="{{ asset('client') }}/assets/js/main.js"></script>
</body>

</html>
