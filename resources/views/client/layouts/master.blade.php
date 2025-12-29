<!doctype html>

<html lang="vi" class="light-style layout-navbar-fixed layout-wide" dir="ltr" data-theme="theme-default"
    data-assets-path="/resources/admin/assets/" data-template="front-pages">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    {{-- SEO Meta Tags --}}
    @if (isset($seoModel) && $seoModel)
        {!! seo()->for($seoModel) !!}
    @else
        {!! seo()->render() !!}
    @endif

    {{-- Meta Keywords --}}
    @include('client.components.seo.meta-keywords')

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @include('client.layouts.sections.styles')
</head>

<body>
    <script src="{{ asset_admin_url('assets/vendor/js/dropdown-hover.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/js/mega-dropdown.js') }}"></script>

    <!-- Navbar: Start -->
    @include('client.layouts.sections.nav')
    <!-- Navbar: End -->

    <!-- Sections:Start -->
    @yield('content')

    <!-- / Sections:End -->

    <!-- Footer: Start -->
    @include('client.layouts.sections.footer')
    <!-- Footer: End -->

    @include('client.layouts.sections.scripts')
</body>

</html>
