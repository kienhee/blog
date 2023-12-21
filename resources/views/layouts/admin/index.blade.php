<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('admin/assets') }}/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title', getEnv('APP_NAME_ADMIN')) | Trần Trung Kiên</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('admin/assets') }}/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/css/core.css"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/css/theme-default.css"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/css/demo.css" />
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/css/coloris.min.css" />
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/css/virtual-select.min.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/libs/apex-charts/apex-charts.css" />
 <script src="{{ asset('client') }}/assets/lib/prismjs/prism.css"></script>
    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="{{ asset('admin/assets') }}/vendor/css/pages/page-misc.css" />
    <!-- Helpers -->
    <script src="{{ asset('admin/assets') }}/vendor/js/helpers.js"></script>
    <script src="https://cdn.tiny.cloud/1/el9eht3oqsjlpvjkdu2mx5gh01fq5xie6zt09pq791iqfhej/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('admin/assets') }}/js/config.js"></script>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
</head>

<body>
    @php
    $checkLayout = $layout ?? 'main';
    @endphp
    @if ($checkLayout == 'main')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('layouts.admin.aside')

            <!-- Layout container -->
            <div class="layout-page">

                @include('layouts.admin.nav')
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->

                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <!-- / Content -->

                    @include('layouts.admin.footer')

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    @elseif($checkLayout == 'auth')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">

                @yield('content')

            </div>
        </div>
    </div>
    @else
    <div class="container-xxl container-p-y">
        @yield('content')
    </div>
    @endif

    <div class="loading-bg ">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>


    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="{{ asset('admin/assets') }}/vendor/libs/jquery/jquery.js"></script>
    <script src="{{ asset('admin/assets') }}/vendor/libs/popper/popper.js"></script>
    <script src="{{ asset('admin/assets') }}/vendor/js/bootstrap.js"></script>
    <script src="{{ asset('admin/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="{{ asset('admin/assets') }}/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('admin/assets') }}/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="{{ asset('admin/assets') }}/js/main.js"></script>
    <script src="{{ asset('admin/assets') }}/js/generateSlug.js"></script>
    <script src="{{ asset('admin/assets') }}/js/virtual-select.min.js"></script>
    <script src="{{ asset('admin/assets') }}/js/coloris.min.js"></script>
    <script src="{{ asset('admin/assets') }}/js/initTinymce.js"></script>
    <script src="{{ asset('vendor') }}/laravel-filemanager/js/stand-alone-button.js"></script>
     <script src="{{ asset('client') }}/assets/lib/prismjs/prism.js"></script>
    <script>
        $('#lfm').filemanager('image');
    </script>
    <!-- Page JS -->
    <script src="{{ asset('admin/assets') }}/js/dashboards-analytics.js"></script>
    <script>
        window.addEventListener("load", function() {
            // Sử dụng sự kiện "load" trên window để kiểm tra khi tất cả tài nguyên đã tải xong.
            var loadingBg = document.querySelector(".loading-bg");
            loadingBg.style.display = "none"; // Ẩn nền loading
        });
    </script>
    <script>
        VirtualSelect.init({
            ele: '#select-multiple',
        });

        Coloris({
            format: 'hex',
            el: '.coloris',
            theme: 'large',
            themeMode: 'light', // light, dark, auto
            swatches: [
                '#264653',
                '#2a9d8f',
                '#e9c46a',
                '#f4a261',
                '#e76f51',
                '#d62828',
                '#023e8a',
                '#0077b6',
                '#0096c7',
                '#00b4d8',
                '#48cae4',
            ]
        });
    </script>
    <script>
        let imgInp = document.getElementById('imgInp')
        let img_preview = document.getElementById('img_preview')
        imgInp?.onchange = evt => {
            const [file] = imgInp.files
            if (file) {
                img_preview.src = URL.createObjectURL(file)
            }
        }
    </script>
</body>

</html>
