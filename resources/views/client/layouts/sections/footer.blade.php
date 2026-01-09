<footer class="landing-footer bg-body footer-text">
    {{-- Footer Top Section --}}
    <div class="footer-top position-relative overflow-hidden z-1">
        <img src="{{ asset_admin_url('assets/img/front-pages/backgrounds/footer-bg-light.png') }}" 
             alt="footer bg"
             class="footer-bg banner-bg-img z-n1" 
             data-app-light-img="front-pages/backgrounds/footer-bg-light.png"
             data-app-dark-img="front-pages/backgrounds/footer-bg-dark.png"
             loading="lazy"
             decoding="async"
             fetchpriority="low" />
        
        <div class="container">
            <div class="row gx-0 gy-4 g-md-5">
                {{-- Column 1: Logo & Contact Info --}}
                <div class="col-lg-5">
                    <a href="{{ route('client.home') }}" class="app-brand-link mb-4">
                        <svg width="202" height="50" viewBox="0 0 202 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M61.9763 37.7246V11.1246H67.1063V21.5746L76.7203 11.1246H83.0283L71.8563 23.0566L83.3323 37.7246H76.9863L67.4103 25.6406L67.1063 25.9826V37.7246H61.9763ZM85.7715 37.7246V18.8766H90.9015V37.7246H85.7715ZM88.3555 16.4446C87.4181 16.4446 86.6581 16.1659 86.0755 15.6086C85.4928 15.0513 85.2015 14.3673 85.2015 13.5566C85.2015 12.7206 85.4928 12.0239 86.0755 11.4666C86.6581 10.9093 87.4181 10.6306 88.3555 10.6306C89.2928 10.6306 90.0528 10.9093 90.6355 11.4666C91.2435 12.0239 91.5475 12.7206 91.5475 13.5566C91.5475 14.3673 91.2435 15.0513 90.6355 15.6086C90.0528 16.1659 89.2928 16.4446 88.3555 16.4446ZM104.011 38.1806C102.086 38.1806 100.376 37.7753 98.8815 36.9646C97.4121 36.1539 96.2595 35.0139 95.4235 33.5446C94.5875 32.0753 94.1695 30.3906 94.1695 28.4906C94.1695 26.5146 94.5748 24.7793 95.3855 23.2846C96.2215 21.7646 97.3741 20.5739 98.8435 19.7126C100.338 18.8513 102.073 18.4206 104.049 18.4206C105.924 18.4206 107.571 18.8259 108.989 19.6366C110.408 20.4473 111.523 21.5619 112.333 22.9806C113.144 24.3739 113.549 25.9573 113.549 27.7306C113.549 27.9839 113.549 28.2753 113.549 28.6046C113.549 28.9086 113.524 29.2253 113.473 29.5546H97.8175V26.4006H108.343C108.267 25.2099 107.824 24.2599 107.013 23.5506C106.228 22.8413 105.24 22.4866 104.049 22.4866C103.163 22.4866 102.352 22.6893 101.617 23.0946C100.883 23.4746 100.3 24.0699 99.8695 24.8806C99.4388 25.6659 99.2235 26.6666 99.2235 27.8826V28.9846C99.2235 30.0233 99.4261 30.9226 99.8315 31.6826C100.237 32.4173 100.794 32.9873 101.503 33.3926C102.238 33.7979 103.061 34.0006 103.973 34.0006C104.911 34.0006 105.696 33.7979 106.329 33.3926C106.963 32.9619 107.444 32.4173 107.773 31.7586H113.017C112.637 32.9493 112.029 34.0386 111.193 35.0266C110.357 35.9893 109.331 36.7619 108.115 37.3446C106.899 37.9019 105.531 38.1806 104.011 38.1806ZM116.04 37.7246V18.8766H120.448L120.866 21.9546H120.98C121.614 20.8399 122.45 19.9786 123.488 19.3706C124.527 18.7373 125.832 18.4206 127.402 18.4206C128.973 18.4206 130.303 18.7626 131.392 19.4466C132.482 20.1053 133.305 21.0806 133.862 22.3726C134.445 23.6646 134.736 25.2606 134.736 27.1606V37.7246H129.644V27.6166C129.644 26.0459 129.315 24.8426 128.656 24.0066C128.023 23.1706 127.022 22.7526 125.654 22.7526C124.793 22.7526 124.02 22.9679 123.336 23.3986C122.652 23.8039 122.12 24.3993 121.74 25.1846C121.36 25.9446 121.17 26.8819 121.17 27.9966V37.7246H116.04ZM137.975 37.7246V11.0106H143.029V21.9926H143.181C143.84 20.8273 144.701 19.9406 145.765 19.3326C146.829 18.7246 148.083 18.4206 149.527 18.4206C151.047 18.4206 152.339 18.7626 153.403 19.4466C154.467 20.1053 155.278 21.0806 155.835 22.3726C156.393 23.6646 156.671 25.2606 156.671 27.1606V37.7246H151.579V27.6166C151.579 26.0459 151.263 24.8426 150.629 24.0066C150.021 23.1706 149.033 22.7526 147.665 22.7526C146.804 22.7526 146.019 22.9679 145.309 23.3986C144.625 23.8039 144.081 24.3993 143.675 25.1846C143.295 25.9446 143.105 26.8819 143.105 27.9966V37.7246H137.975ZM169.027 38.1806C167.102 38.1806 165.392 37.7753 163.897 36.9646C162.428 36.1539 161.275 35.0139 160.439 33.5446C159.603 32.0753 159.185 30.3906 159.185 28.4906C159.185 26.5146 159.59 24.7793 160.401 23.2846C161.237 21.7646 162.39 20.5739 163.859 19.7126C165.354 18.8513 167.089 18.4206 169.065 18.4206C170.94 18.4206 172.586 18.8259 174.005 19.6366C175.424 20.4473 176.538 21.5619 177.349 22.9806C178.16 24.3739 178.565 25.9573 178.565 27.7306C178.565 27.9839 178.565 28.2753 178.565 28.6046C178.565 28.9086 178.54 29.2253 178.489 29.5546H162.833V26.4006H173.359C173.283 25.2099 172.84 24.2599 172.029 23.5506C171.244 22.8413 170.256 22.4866 169.065 22.4866C168.178 22.4866 167.368 22.6893 166.633 23.0946C165.898 23.4746 165.316 24.0699 164.885 24.8806C164.454 25.6659 164.239 26.6666 164.239 27.8826V28.9846C164.239 30.0233 164.442 30.9226 164.847 31.6826C165.252 32.4173 165.81 32.9873 166.519 33.3926C167.254 33.7979 168.077 34.0006 168.989 34.0006C169.926 34.0006 170.712 33.7979 171.345 33.3926C171.978 32.9619 172.46 32.4173 172.789 31.7586H178.033C177.653 32.9493 177.045 34.0386 176.209 35.0266C175.373 35.9893 174.347 36.7619 173.131 37.3446C171.915 37.9019 170.547 38.1806 169.027 38.1806ZM190.328 38.1806C188.403 38.1806 186.693 37.7753 185.198 36.9646C183.729 36.1539 182.576 35.0139 181.74 33.5446C180.904 32.0753 180.486 30.3906 180.486 28.4906C180.486 26.5146 180.891 24.7793 181.702 23.2846C182.538 21.7646 183.691 20.5739 185.16 19.7126C186.655 18.8513 188.39 18.4206 190.366 18.4206C192.241 18.4206 193.887 18.8259 195.306 19.6366C196.725 20.4473 197.839 21.5619 198.65 22.9806C199.461 24.3739 199.866 25.9573 199.866 27.7306C199.866 27.9839 199.866 28.2753 199.866 28.6046C199.866 28.9086 199.841 29.2253 199.79 29.5546H184.134V26.4006H194.66C194.584 25.2099 194.141 24.2599 193.33 23.5506C192.545 22.8413 191.557 22.4866 190.366 22.4866C189.479 22.4866 188.669 22.6893 187.934 23.0946C187.199 23.4746 186.617 24.0699 186.186 24.8806C185.755 25.6659 185.54 26.6666 185.54 27.8826V28.9846C185.54 30.0233 185.743 30.9226 186.148 31.6826C186.553 32.4173 187.111 32.9873 187.82 33.3926C188.555 33.7979 189.378 34.0006 190.29 34.0006C191.227 34.0006 192.013 33.7979 192.646 33.3926C193.279 32.9619 193.761 32.4173 194.09 31.7586H199.334C198.954 32.9493 198.346 34.0386 197.51 35.0266C196.674 35.9893 195.648 36.7619 194.432 37.3446C193.216 37.9019 191.848 38.1806 190.328 38.1806Z"
                                  fill="#F1F2F9" />
                            <path d="M0 20.679C0 9.25828 9.25829 0 20.679 0V28.5654C20.679 39.9861 11.4207 49.2444 0 49.2444V20.679Z"
                                  fill="#EB5E28" />
                            <path d="M23.6787 11.2368C23.6787 5.03088 28.7096 0 34.9155 0C41.1214 0 46.1523 5.03087 46.1523 11.2368V11.8855C46.1523 18.0914 41.1214 23.1223 34.9155 23.1223C28.7096 23.1223 23.6787 18.0914 23.6787 11.8855V11.2368Z"
                                  fill="#667EEA" />
                            <path d="M23.6787 37.3588C23.6787 31.1529 28.7096 26.1221 34.9155 26.1221C41.1214 26.1221 46.1523 31.1529 46.1523 37.3588V38.0076C46.1523 44.2135 41.1214 49.2444 34.9155 49.2444C28.7096 49.2444 23.6787 44.2135 23.6787 38.0076V37.3588Z"
                                  fill="#9E97FF" />
                        </svg>
                    </a>

                    @if(isset($footerSettings))
                        @if(!empty($footerSettings['address']))
                            <p class="footer-text mb-2">
                                <i class="bx bx-map me-2"></i>
                                {{ $footerSettings['address'] }}
                            </p>
                        @endif

                        @if(!empty($footerSettings['phone']))
                            <p class="footer-text mb-2">
                                <i class="bx bx-phone me-2"></i>
                                {{ $footerSettings['phone'] }}
                            </p>
                        @endif

                        @if(!empty($footerSettings['email']))
                            <p class="footer-text mb-3">
                                <i class="bx bx-envelope me-2"></i>
                                {{ $footerSettings['email'] }}
                            </p>
                        @endif
                    @else
                        {{-- Fallback values nếu không có settings --}}
                        <p class="footer-text mb-2">
                            <i class="bx bx-map me-2"></i>
                            Đ. Phạm Hùng, Mễ Trì, Nam Từ Liêm, Hà Nội, Việt Nam
                        </p>
                        <p class="footer-text mb-2">
                            <i class="bx bx-phone me-2"></i>
                            0376173628
                        </p>
                        <p class="footer-text mb-3">
                            <i class="bx bx-envelope me-2"></i>
                            kienhee.it@gmail.com
                        </p>
                    @endif
                </div>

                {{-- Column 2: Quick Links --}}
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-4">Liên kết nhanh</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <a href="{{ route('client.home') }}" class="footer-link">Trang chủ</a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.posts') }}" class="footer-link">Tất cả bài viết</a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.about') }}" class="footer-link">Về chúng tôi</a>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('client.contact') }}" class="footer-link">Liên hệ</a>
                        </li>
                        @guest
                            <li class="mb-3">
                                <a href="{{ route('client.auth.login') }}" class="footer-link">Đăng nhập</a>
                            </li>
                        @endguest
                    </ul>
                </div>

                {{-- Column 3: Explore Hashtags --}}
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-4">Khám phá</h6>
                    @if(isset($allHashtags) && $allHashtags->count() > 0)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($allHashtags as $hashtag)
                                <a href="{{ route('client.hashtag', ['slug' => $hashtag->slug]) }}" 
                                   class="footer-link">
                                    #{{ $hashtag->name }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="footer-text">Chưa có hashtag nào</p>
                    @endif
                </div>

                {{-- Column 4: Map --}}
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-4">Bản đồ</h6>
                    @if(isset($footerSettings['map']) && !empty($footerSettings['map']))
                        <div class="footer-map-wrapper" 
                             style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden; position: relative;">
                            <div style="width: 100%; height: 100%;">
                                {!! $footerSettings['map'] !!}
                            </div>
                        </div>
                    @else
                        <p class="footer-text">Chưa có bản đồ</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Bottom Section --}}
    <div class="footer-bottom py-3">
        <div class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
            {{-- Copyright --}}
            <div class="mb-2 mb-md-0">
                <span class="footer-text">©
                    <script>
                        document.write(new Date().getFullYear());
                    </script>
                </span>
                <a href="{{ route('client.home') }}" 
                   target="_blank"
                   class="fw-medium text-white footer-link">
                    {{ isset($footerSettings) && !empty($footerSettings['site_name']) ? $footerSettings['site_name'] : env('APP_NAME', 'Blog') }},
                </a>
                <span class="footer-text"> Made with ❤️ by
                    <a href="https://kienhee.com" 
                       target="_blank" 
                       class="fw-medium text-white footer-link">Kienhee</a>
                </span>
            </div>

            {{-- Social Media Links --}}
            <div>
                @if(isset($footerSettings['social']))
                    @php
                        $socialLinks = [];
                        $socialConfig = [
                            'facebook' => [
                                'icon' => 'img',
                                'img_light' => 'front-pages/icons/facebook-light.png',
                                'img_dark' => 'front-pages/icons/facebook-dark.png',
                                'alt' => 'facebook'
                            ],
                            'youtube' => [
                                'icon' => 'bx',
                                'class' => 'bxl-youtube',
                                'alt' => 'youtube'
                            ],
                            'twitter' => [
                                'icon' => 'img',
                                'img_light' => 'front-pages/icons/twitter-light.png',
                                'img_dark' => 'front-pages/icons/twitter-dark.png',
                                'alt' => 'twitter'
                            ],
                            'instagram' => [
                                'icon' => 'img',
                                'img_light' => 'front-pages/icons/instagram-light.png',
                                'img_dark' => 'front-pages/icons/instagram-dark.png',
                                'alt' => 'instagram'
                            ],
                            'tiktok' => [
                                'icon' => 'bx',
                                'class' => 'bxl-tiktok',
                                'alt' => 'tiktok'
                            ],
                            'linkedin' => [
                                'icon' => 'bx',
                                'class' => 'bxl-linkedin',
                                'alt' => 'linkedin'
                            ],
                            'telegram' => [
                                'icon' => 'bx',
                                'class' => 'bxl-telegram',
                                'alt' => 'telegram'
                            ],
                            'pinterest' => [
                                'icon' => 'bx',
                                'class' => 'bxl-pinterest',
                                'alt' => 'pinterest'
                            ],
                        ];

                        foreach ($socialConfig as $key => $config) {
                            if (!empty($footerSettings['social'][$key])) {
                                $socialLinks[] = [
                                    'url' => $footerSettings['social'][$key],
                                    'config' => $config,
                                    'key' => $key,
                                ];
                            }
                        }
                    @endphp

                    @foreach($socialLinks as $index => $social)
                        <a href="{{ $social['url'] }}" 
                           class="footer-link {{ $index < count($socialLinks) - 1 ? 'me-3' : '' }}" 
                           target="_blank">
                            @if($social['config']['icon'] === 'img')
                                <img src="{{ asset_admin_url('assets/img/' . $social['config']['img_light']) }}"
                                     alt="{{ $social['config']['alt'] }} icon"
                                     data-app-light-img="{{ $social['config']['img_light'] }}"
                                     data-app-dark-img="{{ $social['config']['img_dark'] }}"
                                     loading="lazy"
                                     decoding="async" />
                            @else
                                <i class="bx {{ $social['config']['class'] }} fs-4"></i>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</footer>
