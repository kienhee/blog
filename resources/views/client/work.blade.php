@extends('layouts.client.index')
@section('title', $work->title)
@section('seo')
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $work->title }}" />
    <meta name="description" content="{{ $work->description }}" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="og:title" content="{{ $work->title }}" />
    <meta property="og:description" content="{{ $work->description }}" />
    <meta property="og:image" content="{{ getEnv('APP_URL') }}{{ $work->cover }}" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="twitter:title" content="{{ $work->title }}" />
    <meta property="twitter:description" content="{{ $work->description }}" />
    <meta property="twitter:image" content="{{ getEnv('APP_URL') }}{{ $work->cover }}" />

    <!-- Meta Tags Generated with https://metatags.io -->
@endsection
@section('content')
    <section class="wrapper">
        <div class="row gap-3 gap-md-0 mb-5">
            <div class="col-md-6">
                <h2 class="text-uppercase">{{ $work->title }}</h2>
                <p class="text-muted">{{ $work->description }}</p>
                @if ($work->website)
                    <a href="{{ $work->website }}" target="_blank" class="btn btn-outline-primary rounded-pill"><i
                            class="fa-solid fa-link"></i> Live website</a>
                @endif

            </div>
            <div class="col-md-6">
                <a href="{{ $work->cover }}" data-lightbox="{{ $work->cover }}" data-title="{{ $work->title }}"
                    title="Phóng to ảnh">

                    <img src="{{ $work->cover }}" class="img-fluid rounded-2" alt="">
                </a>
            </div>
        </div>
        <div class="section-title position-relative text-center  mb-5 pb-2  ">
            <h6 class="position-relative d-inline text-primary ps-4">Thông tin</h6>
            <h3 class="mt-2">Một vài thông tin dự án.
            </h3>
        </div>
        <div class="mb-5">
            {!! $work->content !!}
        </div>
    </section>
    <footer class="py-5  bg-dark text-white">
        <div class=" d-flex justify-content-center gap-3">
            @if (author()->facebook)
                <a href="{{ author()->facebook }}" target="_blank"
                    class="  d-block fw-medium d-flex align-items-center gap-2 ">
                    <i class="fa-brands fa-facebook fs-5"></i><span>Facebook</span></a>
            @endif
            @if (author()->instagram)
                <a href="{{ author()->instagram }}" target="_blank"
                    class="  d-block fw-medium d-flex align-items-center gap-2 ">
                    <i class="fa-brands fa-instagram fs-5"></i><span>Instagram</span></a>
            @endif
            @if (author()->email)
                <a href="{{ author()->email }}" target="_blank"
                    class="  d-block fw-medium d-flex align-items-center gap-2 ">
                    <i class="fa-regular fa-envelope fs-5"></i><span>Email</span></a>
            @endif
            @if (author()->linkedin)
                <a href="{{ author()->linkedin }}" target="_blank"
                    class="  d-block fw-medium d-flex align-items-center gap-2 ">
                    <i class="fa-brands fa-linkedin fs-5"></i><span>Linkedin</span></a>
            @endif
            @if (author()->phone)
                <a href="tel:{{ author()->phone }}" target="_blank"
                    class="d-block fw-medium d-flex align-items-center gap-2 ">
                    <i class="fa-solid fa-square-phone fs-5"></i><span>Telephone</span></a>
            @endif


        </div>
        <hr>
        <small class="d-block text-center ">© kienhee.com 2022 -
            <script>
                document.write(new Date().getFullYear())
            </script>
        </small>
    </footer>
@endsection
