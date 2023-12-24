@extends('layouts.client.index')
@section('title', 'Trang chủ')
@section('seo')
    <!-- Primary Meta Tags -->
    <meta name="title" content="Trần Trung Kiên - Chia sẻ kinh nghiệm lập trình." />
    <meta name="description"
        content="Khám phá những bài viết hữu ích về kinh nghiệm lập trình, học lập trình từ cơ bản đến nâng cao, và cách giải quyết những thách thức thường gặp trong quá trình phát triển phần mềm." />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="og:title" content="Trần Trung Kiên - Chia sẻ kinh nghiệm lập trình." />
    <meta property="og:description"
        content="Khám phá những bài viết hữu ích về kinh nghiệm lập trình, học lập trình từ cơ bản đến nâng cao, và cách giải quyết những thách thức thường gặp trong quá trình phát triển phần mềm." />
    <meta property="og:image" content="{{ getEnv('APP_URL') }}/client/assets/images/about_img.jpg" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ getEnv('APP_URL') }}" />
    <meta property="twitter:title" content="Trần Trung Kiên - Chia sẻ kinh nghiệm lập trình." />
    <meta property="twitter:description"
        content="Khám phá những bài viết hữu ích về kinh nghiệm lập trình, học lập trình từ cơ bản đến nâng cao, và cách giải quyết những thách thức thường gặp trong quá trình phát triển phần mềm." />
    <meta property="twitter:image" content="{{ getEnv('APP_URL') }}/client/assets/images/about_img.jpg" />

    <!-- Meta Tags Generated with https://metatags.io -->
@endsection
@section('content')
    <section class="wrapper">

        <div class="row gap-5 gap-md-0">
            <div class="col-lg-2 d-none d-lg-block">
                <h5 class="title mb-3">📂 Danh mục</h5>
                <ul class="category p-0">
                    <li>
                        <a class="category__item rounded-pill text-muted mb-2 d-block {{ empty(request()->query()) ? 'active' : '' }}"
                            href="/">Tất cả</a>
                    </li>
                    @foreach (getAllCategories() as $category)
                        <li>
                            <a href="?category={{ $category->slug }}"
                                class="category__item rounded-pill text-muted mb-2 d-block {{ request()->input('category') == $category->slug ? 'active' : '' }}"
                                href="/">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-7 mb-5">
                <h5 class="title mb-3">🔍 Tìm kiếm</h5>
                <form action="/">
                    <input type="text" class="form-control rounded-pill text-muted bg-light px-3 mb-4"
                        placeholder="Tìm kiếm bài viết..." name="search">
                </form>

                <div class="sort py-2 d-flex gap-2 justify-content-between justify-content-lg-end align-items-center mb-3">
                    <a class="btn btn-outline-dark btn-sm d-lg-none d-block" data-bs-toggle="offcanvas"
                        href="#categoryMobile" role="button" aria-controls="categoryMobile">
                        <i class="fa-solid fa-list"></i> Danh mục
                    </a>
                    <div class="d-flex gap-2">
                        <a href="?sort=desc"
                            class="text-muted {{ empty(request()->query()) || request()->input('sort') == 'desc' ? 'active' : '' }}">Mới
                            nhất</a>
                        <a href="?sort=asc" class="text-muted {{ request()->input('sort') == 'asc' ? 'active' : '' }}">Cũ
                            nhất</a>
                    </div>
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
                                    <a href="?category={{ $category->slug }}"
                                        class="category__item rounded-pill text-muted mb-2 d-block {{ request()->input('category') == $category->slug ? 'active' : '' }}"
                                        href="/">{{ $category->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3">
                    @if ($posts->count() > 0)
                        @foreach ($posts as $post)
                            <article class="article w-100 rounded-3 shadow-sm overflow-hidden">
                                @if ($post->cover)
                                    <a href="{{ route('client.blog', $post->slug) }}" class="article__image">
                                        <img class="img-fluid" src="{{ $post->cover }}" alt="{{ $post->title }}">
                                    </a>
                                @endif

                                <div class="article__content p-3">
                                    <a href="{{ route('client.blog', $post->slug) }}"
                                        class="fs-4 fw-medium d-block mb-2">{{ $post->title }}</a>
                                    <small
                                        class="text-muted d-block mb-2">{{ $post->created_at->format('d/m/Y - H:m') }}</small>
                                    <small class="text-muted d-block mb-2">By <a
                                            href="{{ route('client.author') }}"><strong>{{ $post->user->full_name }}</strong></a>
                                        - <a
                                            href="/?category={{ $post->category->slug ?? '' }}">{{ $post->category->name ?? 'Danh mục ẩn' }}</a></small>
                                    <p class="text-muted"> {{ $post->description }}</p>
                                    <div class="d-flex gap-2 flex-wrap mt-3">
                                        @foreach (explode(',', $post->tags) as $tag)
                                            <a href="/?tag={{ $tag }}" class="tag">{{ $tag }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @else
                        <h6 class="text-center">Không có bài viết nào</h6>
                    @endif


                    <div class="d-flex justify-content-center align-items-center ">
                        {{ $posts->withQueryString()->links() }}
                    </div>
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
                        <small class="text-muted" id="job-current">{{ author()->career }}</small>
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="title mb-3">🏷️ Khám phá</h5>
                    <div class="bg-white rounded-1 shadow-sm p-3 d-flex gap-2 flex-wrap mt-3">
                        @foreach (getAllTags() as $tag)
                            <a href="/?tag={{ $tag->name }}" class="tag">{{ $tag->name }}</a>
                        @endforeach
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
@endsection
