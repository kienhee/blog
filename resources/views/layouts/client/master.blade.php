@php
    use Illuminate\Support\Facades\App;
        $locale = App::currentLocale(); @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield("title")</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    @include("layouts.client.styles")
</head>

<body class="index-page">
@include("layouts.client.header")
<main class="main">
   @yield("content")
</main>
@include("layouts.client.footer")
<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Preloader -->
<div id="preloader"></div>

@include("layouts.client.scripts")

</body>

</html>
