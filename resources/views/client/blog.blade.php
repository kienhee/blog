@extends('layouts.client.index')
@section('title', $post->title)
@section('seo')
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $post->title_meta }}" />
    <meta name="description" content="{{ $post->description_meta }}" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="og:title" content="{{ $post->title_meta }}" />
    <meta property="og:description" content="{{ $post->description_meta }}" />
    <meta property="og:image" content="{{ getEnv('APP_URL') }}/client/assets/images/about_img.jpg" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="twitter:title" content="{{ $post->title_meta }}" />
    <meta property="twitter:description" content="{{ $post->description_meta }}" />
    <meta property="twitter:image" content="{{ getEnv('APP_URL') }}/client/assets/images/about_img.jpg" />

    <!-- Meta Tags Generated with https://metatags.io -->
@endsection
@section('content')
    <section class="wrapper">
        <div class="row justify-content-center">
            <div class="col-lg-2 d-none d-lg-block">
                <h5 class="title mb-3">📂 Danh mục</h5>
                <ul class="category p-0">
                    <li>
                        <a class="category__item rounded-pill text-muted mb-2 d-block {{ empty(request()->query()) ? 'active' : '' }}"
                            href="/">Tất cả</a>
                    </li>
                    @foreach (getAllCategories() as $category)
                        <li>
                            <a href="/?category={{ $category->slug }}"
                                class="category__item rounded-pill text-muted mb-2 d-block {{ request()->input('category') == $category->slug ? 'active' : '' }}"
                                href="/">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-7">
                <h5 class="title mb-3">🔍 Tìm kiếm</h5>
                <form action="/">
                    <input type="text" class="form-control rounded-pill text-muted bg-light px-3 mb-4"
                        placeholder="Tìm kiếm bài viết..." name="search">
                </form>
                <div
                    class="sort py-2 d-flex gap-2 justify-content-between justify-content-lg-start align-items-center mb-3">
                    <a href="javascript:void(0)" onclick="window.history.back()" class="fw-medium text-secondary ">🔙
                        Quay lại</a>
                    <button class="btn btn-outline-dark btn-sm d-lg-none d-block" data-bs-toggle="offcanvas"
                        href="#categoryMobile" role="button" aria-controls="categoryMobile">
                        <i class="fa-solid fa-list"></i> Danh mục
                    </button>

                </div>
                <div class="offcanvas offcanvas-start" tabindex="-1" id="categoryMobile"
                    aria-labelledby="categoryMobileLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="categoryMobileLabel">📂 Danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="category p-0">
                            <li>
                                <a class="category__item rounded-pill text-muted mb-2 d-block {{ empty(request()->query()) ? 'active' : '' }}"
                                    href="/">Tất cả</a>
                            </li>
                            @foreach (getAllCategories() as $category)
                                <li>
                                    <a href="/?category={{ $category->slug }}"
                                        class="category__item rounded-pill text-muted mb-2 d-block {{ request()->input('category') == $category->slug ? 'active' : '' }}"
                                        href="/">{{ $category->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3 mt-3">
                    <article class="article w-100 rounded-3 shadow-sm overflow-hidden">

                        @if ($post->cover)
                            <img class="img-fluid" src="{{ $post->cover }}" alt="{{ $post->title }}">
                        @endif
                        <div class="article__content p-2
                             p-md-3">
                            <h1 class="fs-3 fw-medium d-block mb-2">{{ $post->title }}</h1>
                            <small class="text-muted d-block mb-2">By <a
                                    href="{{ route('client.author') }}"><strong>{{ $post->user->full_name }}</strong></a>
                                -
                                <a href="/?category={{ $post->category->slug }}">{{ $post->category->name }}</a></small>
                            <small class="text-muted d-block mb-2">{{ $post->created_at->format('d/m/Y - H:m:s') }} - <i
                                    class="fa-regular fa-eye"></i>
                                {{ $post->views }} - {{ estimateReadingTime($post->content) }} phút đọc</small>
                            <hr>
                            <div>
                                {!! $post->content !!}
                            </div>
                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <span>🏷️ Tag:</span>
                                @foreach (explode(',', $post->tags) as $tag)
                                    <a href="/?tag={{ $tag }}" class="tag">{{ $tag }}</a>
                                @endforeach
                            </div>
                            <hr>
                            <section class="p-1">
                                <div class="d-flex flex-start mb-4">
                                    <img class="rounded-circle shadow-1-strong me-3 d-none d-md-block"
                                        src="https://mdbcdn.b-cdn.net/img/Photos/Avatars/img%20(32).webp" alt="avatar"
                                        width="65" height="65" />
                                    <div class="card w-100">
                                        <div class="card-body p-4">
                                            <div class="">
                                                <img class="rounded-circle shadow-1-strong mb-2 d-md-none"
                                                    src="https://mdbcdn.b-cdn.net/img/Photos/Avatars/img%20(32).webp"
                                                    alt="avatar" width="65" height="65" />
                                                <h5>Johny Cash</h5>
                                                <p class="small">3 hours ago</p>
                                                <p>
                                                    Cras sit amet nibh libero, in gravida nulla. Nulla
                                                    vel metus scelerisque
                                                    ante sollicitudin. Cras purus odio, vestibulum in
                                                    vulputate at, tempus
                                                    viverra turpis.
                                                </p>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <a href="#!" class="link-muted me-2"><i
                                                                class="fas fa-thumbs-up me-1"></i>132</a>
                                                        <a href="#!" class="link-muted"><i
                                                                class="fas fa-thumbs-down me-1"></i>15</a>
                                                    </div>
                                                    <a href="#!" class="link-muted"><i
                                                            class="fas fa-reply me-1"></i>
                                                        Reply</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="mt-5">
                                <h5 class="title mb-4 mt-4 text-center">🗨️ Để lại lời nhắn</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="fullName" class="form-label">Họ và tên
                                        </label>
                                        <input type="text" class="form-control" id="fullName" required>
                                    </div>
                                    <div class="col-md-6  mb-3">
                                        <label for="email" class="form-label">Email
                                        </label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="content" class="form-label">Nội dung</label>
                                        <textarea class="form-control" id="content" rows="3"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100">Bình luận</button>
                                    </div>
                                </div>
                                <small class="d-block text-center text-muted d-lg-none">© kienhee.com 2022 -
                                    <script>
                                        document.write(new Date().getFullYear())
                                    </script>
                                </small>
                            </section>
                        </div>
                    </article>

                </div>
            </div>
            <div class="col-lg-3 d-none d-lg-block">
                <div class="mb-4">
                    <h5 class="title mb-3">💻 Profile</h5>
                    <div
                        class="bg-white rounded-1 shadow-sm p-3 d-flex justify-content-center align-items-center flex-column gap-2">
                        <img src="{{ asset('client') }}/assets/images/avatar.png" class="profile-image"
                            alt="Trần Trung Kiên">
                        <h5 class="mb-0"><i>Trần Trung Kiên</i></h5>
                        <small class="text-muted" id="job-current">Software Engineer</small>
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="title mb-3">🌟 Service</h5>
                    <div class="bg-white rounded-1 shadow-sm p-3 contact d-flex flex-column gap-2 ">
                        <a href="{{ route('client.author') }}"
                            class="contact-social  d-block fw-medium d-flex align-items-center gap-2 ">
                            <i class="fa-solid fa-desktop"></i><span>Lập trình web</span></a>
                        <a href="{{ route('client.author') }}"
                            class="contact-social  d-block fw-medium d-flex align-items-center gap-2 ">
                            <i class="fa-solid fa-mobile-screen-button"></i><span>Lập trình mobile</span></a>
                        <a href="{{ route('client.author') }}"
                            class="contact-social  d-block fw-medium d-flex align-items-center gap-2 ">
                            <i class="fa-brands fa-figma"></i><span>UI/UX</span></a>
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="title mb-3">🗨️ Liên hệ</h5>
                    <div class="bg-white rounded-1 shadow-sm p-3 contact d-flex flex-column gap-2 ">
                        @if (author()->facebook)
                            <a href="{{ author()->facebook }}" target="_blank"
                                class=" contact-social d-block fw-medium d-flex align-items-center gap-2 ">
                                <i class="fa-brands fa-facebook fs-5"></i><span>Facebook</span></a>
                        @endif
                        @if (author()->instagram)
                            <a href="{{ author()->instagram }}" target="_blank"
                                class=" contact-social d-block fw-medium d-flex align-items-center gap-2 ">
                                <i class="fa-brands fa-instagram fs-5"></i><span>Instagram</span></a>
                        @endif
                        @if (author()->email)
                            <a href="{{ author()->email }}" target="_blank"
                                class=" contact-social d-block fw-medium d-flex align-items-center gap-2 ">
                                <i class="fa-regular fa-envelope fs-5"></i><span>Email</span></a>
                        @endif
                        @if (author()->linkedin)
                            <a href="{{ author()->linkedin }}" target="_blank"
                                class=" contact-social d-block fw-medium d-flex align-items-center gap-2 ">
                                <i class="fa-brands fa-linkedin fs-5"></i><span>Linkedin</span></a>
                        @endif
                        @if (author()->phone)
                            <a href="tel:{{ author()->phone }}" target="_blank"
                                class=" contact-social d-block fw-medium d-flex align-items-center gap-2 ">
                                <i class="fa-solid fa-square-phone fs-5"></i><span>Telephone</span></a>
                        @endif
                    </div>
                </div>
                <small class="d-block text-center text-muted">© kienhee.com 2022 -
                    <script>
                        document.write(new Date().getFullYear())
                    </script>
                </small>
            </div>
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
                    class="  d-block fw-medium d-flex align-items-center gap-2 ">
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
