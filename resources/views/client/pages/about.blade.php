@extends('client.layouts.master')
@section('title', 'Về chúng tôi')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset_client_url('css/post.css') }}">
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="section-py pb-5 bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div class="pe-lg-4">
                        <h1 class="display-3 fw-bold mb-4">Xin chào, Tôi là Developer</h1>
                        <p class="lead mb-4 opacity-90">
                            Một Full-stack Developer đam mê với công nghệ, luôn tìm kiếm những giải pháp sáng tạo và hiệu
                            quả
                            để xây dựng các ứng dụng web hiện đại.
                        </p>
                        <div class="d-flex gap-3">
                            <a href="#"
                                class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                style="width: 48px; height: 48px;" target="_blank" title="GitHub">
                                <i class="bx bxl-github fs-4"></i>
                            </a>
                            <a href="#"
                                class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                style="width: 48px; height: 48px;" target="_blank" title="LinkedIn">
                                <i class="bx bxl-linkedin fs-4"></i>
                            </a>
                            <a href="#"
                                class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                style="width: 48px; height: 48px;" target="_blank" title="Facebook">
                                <i class="bx bxl-facebook fs-4"></i>
                            </a>
                            <a href="#"
                                class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                style="width: 48px; height: 48px;" target="_blank" title="Twitter">
                                <i class="bx bxl-twitter fs-4"></i>
                            </a>
                            <a href="mailto:contact@example.com"
                                class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                style="width: 48px; height: 48px;" title="Email">
                                <i class="bx bx-envelope fs-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ps-lg-4">
                        <div class="position-relative ms-auto" style="max-width: 500px;">
                            <div class="mx-auto rounded-circle overflow-hidden border border-white border-opacity-50"
                                style="width: 320px; height: 320px; border-width: 5px !important;">
                                <img src="{{ asset_client_url('images/author.jpg') }}" alt="Developer Profile"
                                    class="w-100 h-100" style="object-fit: cover;" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content Section -->
    <section>
        <div class="container">
            <!-- About Section -->
            <div class="py-5">
                <h2 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Về tôi<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h2>
                <div>
                    <p class="fs-5 lh-lg text-muted mb-3">
                        Tôi là một Full-stack Developer với nhiều năm kinh nghiệm trong việc phát triển các ứng dụng
                        web từ đầu đến cuối. Tôi đam mê với việc tạo ra những sản phẩm chất lượng cao, có trải
                        nghiệm
                        người dùng tốt và hiệu năng cao.
                    </p>
                    <p class="fs-5 lh-lg text-muted mb-0">
                        Với kiến thức sâu rộng về cả frontend và backend, tôi có thể làm việc độc lập hoặc trong một
                        nhóm để đưa ra các giải pháp tối ưu cho các vấn đề phức tạp. Tôi luôn cập nhật những công
                        nghệ
                        mới nhất và không ngừng học hỏi để nâng cao kỹ năng của mình.
                    </p>
                </div>
            </div>

            <!-- Skills Section -->
            <div class="py-5">
                <h2 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Kỹ năng & Công nghệ<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-semibold mb-3 d-flex align-items-center">
                                    <i class="bx bx-code-alt text-primary me-2 fs-5"></i>
                                    Frontend
                                </h4>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill bg-label-secondary">HTML5</span>
                                    <span class="badge rounded-pill bg-label-secondary">CSS3</span>
                                    <span class="badge rounded-pill bg-label-secondary">JavaScript</span>
                                    <span class="badge rounded-pill bg-label-secondary">Vue.js</span>
                                    <span class="badge rounded-pill bg-label-secondary">React</span>
                                    <span class="badge rounded-pill bg-label-secondary">Bootstrap</span>
                                    <span class="badge rounded-pill bg-label-secondary">Tailwind CSS</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-semibold mb-3 d-flex align-items-center">
                                    <i class="bx bx-server text-primary me-2 fs-5"></i>
                                    Backend
                                </h4>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill bg-label-secondary">PHP</span>
                                    <span class="badge rounded-pill bg-label-secondary">Laravel</span>
                                    <span class="badge rounded-pill bg-label-secondary">Node.js</span>
                                    <span class="badge rounded-pill bg-label-secondary">Express</span>
                                    <span class="badge rounded-pill bg-label-secondary">RESTful API</span>
                                    <span class="badge rounded-pill bg-label-secondary">GraphQL</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-semibold mb-3 d-flex align-items-center">
                                    <i class="bx bx-data text-primary me-2 fs-5"></i>
                                    Database & Tools
                                </h4>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill bg-label-secondary">MySQL</span>
                                    <span class="badge rounded-pill bg-label-secondary">PostgreSQL</span>
                                    <span class="badge rounded-pill bg-label-secondary">MongoDB</span>
                                    <span class="badge rounded-pill bg-label-secondary">Redis</span>
                                    <span class="badge rounded-pill bg-label-secondary">Git</span>
                                    <span class="badge rounded-pill bg-label-secondary">Docker</span>
                                    <span class="badge rounded-pill bg-label-secondary">AWS</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Experience Section -->
            <div class="py-5">
                <h2 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Kinh nghiệm<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h2>
                <div class="position-relative ps-5">
                    <!-- Vertical Line -->
                    <div class="position-absolute top-0 bottom-0 border-start border-2 border-secondary"
                        style="left: 15px;"></div>

                    <!-- Timeline Item 1 -->
                    <div class="position-relative mb-4">
                        <div class="position-absolute rounded-circle bg-primary border border-white"
                            style="left: -42px; top: 1.5rem; width: 20px; height: 20px; border-width: 4px !important; box-shadow: 0 0 0 2px #667eea; z-index: 1;">
                        </div>
                        <div class="card border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="h5 fw-semibold mb-2">Full-stack Developer</h4>
                                <p class="text-primary fw-semibold mb-2">RikkeiSoft | 2023 - Hiện tại</p>
                                <ul class="mb-0 lh-lg">
                                    <li>
                                        Phát triển module và bảo trì các dự án nội bộ của công ty: <strong> Intranet system,
                                            LMS, CRM và CMS system</strong>.
                                    </li>
                                    <li>
                                        Thiết kế, xây dựng và tối ưu RESTful API cho các ứng dụng web nội bộ và khách hàng.
                                    </li>
                                    <li>
                                        Tham gia phân tích yêu cầu, đề xuất giải pháp kỹ thuật phù hợp với từng dự án.
                                    </li>
                                    <li>
                                        Tối ưu hiệu năng hệ thống, xử lý các vấn đề về bảo mật, tối ưu truy vấn database.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- CTA: Start -->
    <section id="landingCTA" class="section-py landing-cta position-relative p-lg-0 pb-0 mb-5">
        <img src="{{ asset_admin_url('assets/img/front-pages/backgrounds/cta-bg-light.png') }}"
            class="position-absolute bottom-0 end-0 scaleX-n1-rtl h-100 w-100 z-n1" alt="cta image"
            data-app-light-img="front-pages/backgrounds/cta-bg-light.png"
            data-app-dark-img="front-pages/backgrounds/cta-bg-dark.png" />
        <div class="container">
            <div class="row align-items-center gy-5 gy-lg-0">
                <div class="col-lg-6 text-center text-lg-start">
                    <h6 class="h2 text-primary fw-bold mb-1">Có một dự án trong đầu?</h6>
                    <p class="fw-medium mb-4">Hãy liên hệ với tôi để thảo luận về dự án của bạn. Tôi luôn sẵn sàng hợp tác
                        và đưa ra các giải pháp sáng tạo.
                    </p>
                    <a href="{{ route('client.contact') }}" class="btn btn-primary">Liên hệ ngay</a>
                </div>
                <div class="col-lg-6 pt-lg-5 text-center text-lg-end">
                    <img src="{{ asset_admin_url('assets/img/front-pages/landing-page/cta-dashboard.png') }}"
                        alt="cta dashboard" class="img-fluid" />
                </div>
            </div>
        </div>
    </section>
    <!-- CTA: End -->
@endsection
