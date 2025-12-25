@extends('emails.layouts.master')

@section('title', 'Đặt lại mật khẩu')

@section('content')
    <p style="margin:0 0 12px; color:#1F2937; font-size:18px; font-weight:700;">
        Xin chào {{ $userName }},
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Chúng tôi nhận được yêu cầu <strong>đặt lại mật khẩu</strong> cho tài khoản của bạn.
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Nhấn vào nút bên dưới để đặt lại mật khẩu. Liên kết có hiệu lực trong {{ $expiresIn }}.
    </p>

    <p style="margin:16px 0 20px; text-align:center;">
        <a href="{{ $resetUrl }}" style="display:inline-block; padding:12px 20px; border-radius:8px; text-decoration:none; background:#FF6A3D; color:#fff; font-weight:700;">
            Đặt lại mật khẩu
        </a>
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email. Tài khoản của bạn vẫn an toàn.
    </p>

    <p style="margin:0 0 6px; color:#6B7280; font-size:14px; line-height:1.5;">
        Một số lưu ý bảo mật:
    </p>
    <ul style="margin:0 0 12px 18px; padding:0; color:#6B7280; font-size:14px; line-height:1.55;">
        <li>Không chia sẻ liên kết đặt lại mật khẩu cho bất kỳ ai.</li>
        <li>Sau khi đặt lại, hãy dùng mật khẩu mạnh và không trùng mật khẩu cũ.</li>
        <li>Liên kết này sẽ hết hạn sau {{ $expiresIn }} hoặc khi bạn yêu cầu lại.</li>
    </ul>

    <p style="margin:0; color:#9CA3AF; font-size:13px; line-height:1.4;">
        Nếu nút không bấm được, hãy copy và mở liên kết này trong trình duyệt:<br>
        <span style="color:#2563EB; word-break:break-all;">{{ $resetUrl }}</span>
    </p>
@endsection
