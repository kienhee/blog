@extends('client.layouts.master')
@section('title', 'Tác giả')
@section('content')
    <!-- Hero Section -->
    <section class="section-py pb-5">
        <div class="main-container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="pe-lg-4">
                        <h1 class="display-3 fw-bold mb-4">Xin chào, tôi là Trần Trung Kiên</h1>
                        <p class="lead mb-4 opacity-90">
                            Một Full-stack Developer đam mê với công nghệ, luôn tìm kiếm những giải pháp sáng tạo và hiệu
                            quả
                            để xây dựng các ứng dụng web hiện đại.
                        </p>
                        <div class="d-flex gap-3">
                            @if (!empty($socialLinks['github']))
                                <a href="{{ $socialLinks['github'] }}"
                                    class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                    style="width: 48px; height: 48px;" target="_blank" rel="noopener noreferrer" title="GitHub">
                                    <i class="bx bxl-github fs-4"></i>
                                </a>
                            @endif
                            @if (!empty($socialLinks['linkedin']))
                                <a href="{{ $socialLinks['linkedin'] }}"
                                    class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                    style="width: 48px; height: 48px;" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                                    <i class="bx bxl-linkedin fs-4"></i>
                                </a>
                            @endif
                            @if (!empty($socialLinks['facebook']))
                                <a href="{{ $socialLinks['facebook'] }}"
                                    class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                    style="width: 48px; height: 48px;" target="_blank" rel="noopener noreferrer" title="Facebook">
                                    <i class="bx bxl-facebook fs-4"></i>
                                </a>
                            @endif
                            @if (!empty($socialLinks['twitter']))
                                <a href="{{ $socialLinks['twitter'] }}"
                                    class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                    style="width: 48px; height: 48px;" target="_blank" rel="noopener noreferrer" title="Twitter">
                                    <i class="bx bxl-twitter fs-4"></i>
                                </a>
                            @endif
                            @if (!empty($socialLinks['email']))
                                <a href="mailto:{{ $socialLinks['email'] }}"
                                    class="rounded-circle d-flex align-items-center justify-content-center text-decoration-none bg-white bg-opacity-25 border border-white border-opacity-50"
                                    style="width: 48px; height: 48px;" title="Email">
                                    <i class="bx bx-envelope fs-4"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="ps-lg-4">
                        <div class="position-relative ms-auto" style="max-width: 500px;">
                            <div class="mx-auto  overflow-hidden border border-white border-opacity-50"
                                style="width: 100%; height: 100%; border-width: 5px !important;">
                                <img src="{{ asset_client_url('images/author.jpg') }}" alt="Developer Profile"
                                    class="w-100 h-100 rounded-3" style="object-fit: cover;" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content Section -->
    <section>
        <div class="main-container">
            <!-- About Section -->
            <div class="py-4">
                <h3 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Về tôi<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h3>
                <div>
                    <p class="fs-5 lh-lg mb-3">
                        Tôi là một Full-stack Developer với nhiều năm kinh nghiệm phát triển ứng dụng web. Đam mê tạo ra
                        những sản phẩm chất lượng cao, có trải nghiệm người dùng tốt và hiệu năng tối ưu.
                    </p>
                    <p class="fs-5 lh-lg mb-0">
                        Với kiến thức sâu về frontend và backend, tôi luôn cập nhật công nghệ mới và không ngừng học hỏi.
                        Website <a href="https://kienhee.com/" target="_blank" class="text-primary">kienhee.com</a> là blog
                        cá nhân chuyên về lập trình, được thành lập từ <span
                            class="fw-semibold text-primary">01-01-2026</span> và vẫn đang hoạt động.
                    </p>
                </div>
            </div>

            <div class="py-4">
                <h3 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Mục Tiêu<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h3>
                <div>
                    <p class="fs-5 lh-lg mb-3">
                        Kienhee.com được tạo ra với mục đích chia sẻ kiến thức lập trình và kinh nghiệm thực tế trong công
                        việc. Blog sẽ tiếp tục mở rộng với nhiều chủ đề đa dạng, hỗ trợ nhu cầu tự học của sinh viên và các
                        bạn đã đi làm.
                    </p>
                    <p class="fs-5 lh-lg mb-0">
                        Trong tương lai, website sẽ bổ sung thêm các bài viết về SEO, Digital Marketing và các chủ đề liên
                        quan. Rất mong nhận được sự đón đọc và ủng hộ từ các bạn.
                    </p>
                </div>
            </div>

            <!-- Skills Section -->
            <div class="py-4">
                <h3 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Kỹ năng & Công nghệ<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h3>
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
                                    <span class="badge rounded-pill bg-label-secondary">Cursor AI</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Experience Section -->
            <div class="py-4">
                <h3 class="fw-bold mb-4 pb-3 position-relative d-inline-block">Kinh nghiệm<span
                        class="position-absolute bottom-0 start-0 bg-primary rounded"
                        style="width: 80px; height: 4px;"></span></h3>
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
                    <h6 class="h3 text-primary fw-bold mb-1">Có một dự án trong đầu?</h6>
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
