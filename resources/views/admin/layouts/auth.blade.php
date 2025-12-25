<!DOCTYPE html>

<html lang="en" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="/resources/admin/assets/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title') | {{ env('APP_NAME') }} </title>
    <meta name="description" content="" />
    @include('admin.layouts.sections.styles')
</head>

<body>
    @yield('content')
    @include('admin.layouts.sections.scripts')
</body>

</html>
