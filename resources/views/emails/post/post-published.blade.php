@extends('emails.layouts.master')

@section('title', 'Bài viết đã được đăng')

@section('content')
    <p style="margin:0 0 12px; color:#1F2937; font-size:18px; font-weight:700;">
        Xin chào {{ $post->user->full_name ?? $post->user->email }},
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Bài viết của bạn <strong>"{{ $post->title }}"</strong> đã được đăng thành công!
    </p>

    <div style="margin:16px 0; padding:14px; background:#F0FDF4; border-left:4px solid #22C55E; border-radius:6px;">
        <p style="margin:0; color:#166534; font-size:14px; font-weight:600;">✓ Bài viết đã được xuất bản</p>
    </div>

    <p style="margin:16px 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Bạn có thể xem bài viết của mình tại:
    </p>

    <p style="margin:0 0 20px; text-align:center;">
        <a href="{{ url('/posts/' . $post->slug) }}"
            style="display:inline-block; padding:12px 20px; border-radius:8px; text-decoration:none; background:#FF6A3D; color:#fff; font-weight:700;">
            Xem bài viết
        </a>
    </p>

    <p style="margin:0; color:#9CA3AF; font-size:13px; line-height:1.4;">
        Thời gian đăng: {{ now()->format('d/m/Y H:i:s') }}
    </p>
@endsection
