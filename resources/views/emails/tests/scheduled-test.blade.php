@extends('emails.layouts.master')

@section('title', 'Kiểm tra Schedule Hệ thống')

@section('content')
    <h2 style="margin:0 0 12px; color:#1F2937; font-size:20px; font-weight:700;">Chào mừng {{ $full_name }} 🎉</h2>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Đây là email kiểm tra schedule tự động của hệ thống.
    </p>

    <p style="margin:0 0 10px; color:#6B7280; font-size:15px; line-height:1.5;">Dưới đây là một số thông tin kỹ thuật:</p>

    <ul style="padding-left:18px; margin:6px 0 16px; color:#6B7280; line-height:1.55; font-size:15px;">
        <li><strong>Thời gian gửi:</strong> {{ $sentAt }}</li>
        <li><strong>Môi trường:</strong> {{ $environment }}</li>
        <li><strong>Khoảng thời gian kiểm tra:</strong> {{ $interval }} phút</li>
    </ul>

    <p style="margin:0 0 10px; color:#6B7280; font-size:15px; line-height:1.5;">Nếu bạn nhận được email này, đồng nghĩa rằng:</p>

    <div style="margin-top:16px; padding:14px; background:#FFF3EE; border-radius:6px; font-size:13px; color:#1F2937;">
        <p style="margin:0; color:#1F2937; font-size:14px;">Hệ thống schedule và queue đang hoạt động bình thường.</p>
    </div>

    <div style="margin-top:20px; padding:14px; background:#FEF3C7; border-left:4px solid #F59E0B; border-radius:6px; font-size:13px; color:#92400E;">
        <p style="margin:0 0 10px; color:#92400E; font-size:14px; font-weight:600;">
            ⚠️ Lưu ý quan trọng:
        </p>
        <p style="margin:0 0 12px; color:#92400E; font-size:13px; line-height:1.6;">
            Nếu bạn không tắt chức năng kiểm tra schedule trong hệ thống, email này sẽ tiếp tục được gửi đến bạn theo khoảng thời gian đã cấu hình ({{ $interval }} phút).
        </p>
        <p style="margin:0 0 10px; color:#1E40AF; font-size:14px; font-weight:600;">
            📋 Hướng dẫn tắt chức năng kiểm tra:
        </p>
        <ol style="margin:0; padding-left:20px; color:#1E40AF; font-size:13px; line-height:1.8;">
            <li>Đăng nhập vào hệ thống quản trị</li>
            <li>Vào mục <strong>"Cài đặt"</strong> → Tab <strong>"Kiểm tra hệ thống"</strong></li>
            <li>Tắt switch <strong>"Trạng thái"</strong> sang OFF</li>
            <li>Click nút <strong>"Lưu cài đặt"</strong></li>
        </ol>
    </div>

    <p style="margin:20px 0 0; color:#6B7280; font-size:15px; line-height:1.5;">
        Chúc bạn có trải nghiệm tốt cùng hệ thống!
    </p>

@endsection

