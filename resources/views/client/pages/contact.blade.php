@extends('client.layouts.master')
@section('title', 'Liên hệ')
@section('content')
    <section class="section-py first-section-pt help-center-header position-relative overflow-hidden">
        <img class="banner-bg-img" src="{{ asset_admin_url('assets/img/pages/header.png') }}" alt="Help center header" />
        <h2 class="text-center">Liên hệ</h2>
        <p class="text-center px-3 mb-0">Có câu hỏi hoặc góp ý? Hãy gửi tin nhắn cho chúng tôi</p>
    </section>
    <section id="landingContact" class="section-py bg-body landing-contact">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-5">
                    <div class="contact-img-box position-relative border p-2 h-100">
                        <img src="{{ asset_admin_url('assets/img/front-pages/icons/contact-border.png') }}"
                            alt="contact border"
                            class="contact-border-img position-absolute d-none d-md-block scaleX-n1-rtl" />
                        <img src="{{ asset_admin_url('assets/img/front-pages/landing-page/contact-customer-service.png') }}"
                            alt="contact customer service" class="contact-img w-100 scaleX-n1-rtl" />
                        <div class="py-3 px-1 ">
                            <div class="row gy-3 gx-md-4">
                                <div class="col-md-6 col-lg-12 col-xl-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-label-primary rounded p-2 me-2"><i
                                                class="bx bx-envelope bx-sm"></i></div>
                                        <div>
                                            <p class="mb-0">Email</p>
                                            <h5 class="mb-0">
                                                <a href="mailto:example@gmail.com"
                                                    class="text-heading">example@gmail.com</a>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-12 col-xl-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-label-success rounded p-2 me-2">
                                            <i class="bx bx-phone-call bx-sm"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0">Điện thoại</p>
                                            <h5 class="mb-0"><a href="tel:+1234-568-963" class="text-heading">+1234
                                                    568 963</a></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">Gửi tin nhắn</h4>
                            <form>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label" for="contact-form-fullname">Họ và tên</label>
                                        <input type="text" class="form-control" id="contact-form-fullname"
                                            placeholder="Nguyễn Văn A" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="contact-form-email">Email</label>
                                        <input type="text" id="contact-form-email" class="form-control"
                                            placeholder="nguyenvana@gmail.com" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="contact-form-message">Tin nhắn</label>
                                        <textarea id="contact-form-message" class="form-control" rows="9" placeholder="Viết tin nhắn của bạn..."></textarea>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
