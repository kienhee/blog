@extends('emails.layouts.master')

@section('title', 'Chào mừng bạn đến với hệ thống')

@section('content')
    <h2 style="margin:0 0 12px; color:#1F2937; font-size:20px; font-weight:700;">Chào mừng {{ $full_name }} 🎉</h2>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Cảm ơn bạn đã kết nối với hệ thống.
    </p>

    <p style="margin:0 0 10px; color:#6B7280; font-size:15px; line-height:1.5;">Dưới đây là một số thông tin kỹ thuật:</p>

    <ul style="padding-left:18px; margin:6px 0 16px; color:#6B7280; line-height:1.55; font-size:15px;">
        <li><strong>Thời gian gửi:</strong> {{ $sentAt }}</li>
        <li><strong>Môi trường:</strong> {{ $environment }}</li>
    </ul>

    <p style="margin:0 0 10px; color:#6B7280; font-size:15px; line-height:1.5;">Nếu bạn nhận được email này, đồng nghĩa rằng:</p>

    <div style="margin-top:16px; padding:14px; background:#FFF3EE; border-radius:6px; font-size:13px; color:#1F2937;">
        <p style="margin:0; color:#1F2937; font-size:14px;">Kết nối gửi mail đang hoạt động bình thường.</p>
    </div>

    <p style="margin:20px 0 0; color:#6B7280; font-size:15px; line-height:1.5;">
        Chúc bạn có trải nghiệm tốt cùng hệ thống!
    </p>

@endsection
