@extends('emails.layouts.master')

@section('title', 'Thông báo liên hệ mới - ' . env('APP_NAME'))

@section('content')
    <p style="margin:0 0 12px; color:#1F2937; font-size:18px; font-weight:700;">
        Thông báo: Có liên hệ mới
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Bạn có một liên hệ mới từ khách hàng trên website.
    </p>

    <div style="margin:20px 0; padding:16px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
        <h3 style="margin:0 0 16px; color:#1F2937; font-size:16px; font-weight:600;">Thông tin liên hệ</h3>
        
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="padding:8px 0; color:#4B5563; font-size:14px; font-weight:600; width:140px;">Họ và tên:</td>
                <td style="padding:8px 0; color:#1F2937; font-size:14px;">{{ $contact->full_name }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#4B5563; font-size:14px; font-weight:600;">Email:</td>
                <td style="padding:8px 0; color:#1F2937; font-size:14px;">
                    <a href="mailto:{{ $contact->email }}" style="color:#FF6A3D; text-decoration:none;">{{ $contact->email }}</a>
                </td>
            </tr>
            @if($contact->phone)
            <tr>
                <td style="padding:8px 0; color:#4B5563; font-size:14px; font-weight:600;">Số điện thoại:</td>
                <td style="padding:8px 0; color:#1F2937; font-size:14px;">
                    <a href="tel:{{ $contact->phone }}" style="color:#FF6A3D; text-decoration:none;">{{ $contact->phone }}</a>
                </td>
            </tr>
            @endif
            <tr>
                <td style="padding:8px 0; color:#4B5563; font-size:14px; font-weight:600;">Tiêu đề:</td>
                <td style="padding:8px 0; color:#1F2937; font-size:14px; font-weight:600;">{{ $contact->subject }}</td>
            </tr>
        </table>
    </div>

    <div style="margin:20px 0; padding:16px; background:#EFF6FF; border-left:4px solid #FF6A3D; border-radius:6px;">
        <p style="margin:0 0 8px; color:#1F2937; font-size:14px; font-weight:600;">Tin nhắn:</p>
        <p style="margin:0; color:#4B5563; font-size:14px; line-height:1.6; white-space: pre-wrap;">{{ $contact->message }}</p>
    </div>

    <div style="margin:20px 0; padding:12px; background:#F3F4F6; border-radius:6px;">
        <p style="margin:0; color:#6B7280; font-size:13px;">
            <strong>Thời gian:</strong> {{ $contact->created_at->format('d/m/Y H:i:s') }}
        </p>
    </div>

    <div style="margin:24px 0; text-align:center;">
        <a href="{{ route('admin.contacts.show', $contact->id) }}" 
           style="display:inline-block; padding:12px 20px; border-radius:8px; text-decoration:none; background:#FF6A3D; color:#fff; font-weight:700;">
            Xem chi tiết
        </a>
    </div>

    <p style="margin:20px 0 0; color:#9CA3AF; font-size:13px; line-height:1.4;">
        Trân trọng,<br>
        Đội ngũ {{ env('APP_NAME') }}
    </p>
@endsection
