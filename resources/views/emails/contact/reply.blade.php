@extends('emails.layouts.master')

@section('title', 'Phản hồi từ ' . env('APP_NAME'))

@section('content')
    <p style="margin:0 0 12px; color:#1F2937; font-size:18px; font-weight:700;">
        Xin chào {{ $contact->full_name }},
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi của chúng tôi về yêu cầu của bạn:
    </p>

    <div style="margin:16px 0; padding:14px; background:#F3F4F6; border-left:4px solid #3B82F6; border-radius:6px;">
        <p style="margin:0 0 8px; color:#1F2937; font-size:14px; font-weight:600;">Tin nhắn gốc của bạn:</p>
        <p style="margin:0 0 4px; color:#6B7280; font-size:13px;"><strong>Chủ đề:</strong> {{ $contact->subject }}</p>
        <p style="margin:0; color:#6B7280; font-size:13px; white-space: pre-wrap;">{{ $contact->message }}</p>
    </div>

    <div style="margin:20px 0; padding:16px; background:#EFF6FF; border-left:4px solid #3B82F6; border-radius:6px;">
        <p style="margin:0 0 8px; color:#1F2937; font-size:14px; font-weight:600;">Phản hồi của chúng tôi:</p>
        <p style="margin:0 0 4px; color:#1F2937; font-size:14px;"><strong>{{ $reply->subject }}</strong></p>
        <p style="margin:0; color:#4B5563; font-size:14px; line-height:1.6; white-space: pre-wrap;">{{ $reply->message }}</p>
    </div>

    <p style="margin:20px 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Nếu bạn có thêm câu hỏi hoặc cần hỗ trợ thêm, vui lòng liên hệ với chúng tôi.
    </p>

    <p style="margin:0; color:#9CA3AF; font-size:13px; line-height:1.4;">
        Trân trọng,<br>
        Đội ngũ {{ env('APP_NAME') }}
    </p>
@endsection
