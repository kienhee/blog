@extends('emails.layouts.master')

@section('title', 'Nhắc nhở kiểm tra tài chính')

@section('content')
@php
    $monthNames = [
        1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
        5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
        9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12',
    ];
    $monthName = $monthNames[$financeMonth->month] ?? 'Tháng ' . $financeMonth->month;
    $year = $financeMonth->financeYear->year;
    $monthUrl = route('admin.finance.years.months.show', [
        'yearId' => $financeMonth->financeYear->id,
        'month' => $financeMonth->month
    ]);
@endphp

<div style="color:#1F2937; font-size:16px; line-height:24px;">
    <h1 style="margin:0 0 20px 0; font-size:24px; font-weight:600; color:#111827;">
        Nhắc nhở kiểm tra tài chính
    </h1>
    
    <p style="margin:0 0 16px 0;">
        Xin chào <strong>{{ $user->full_name ?? $user->email }}</strong>,
    </p>
    
    <p style="margin:0 0 16px 0;">
        Tháng <strong>{{ $monthName }}/{{ $year }}</strong> đã kết thúc. Vui lòng kiểm tra và xác nhận lại các khoản chi tiêu trong tháng, sau đó khóa tháng để bảo vệ dữ liệu.
    </p>
    
    <p style="margin:0 0 24px 0;">
        Sau khi khóa, bạn sẽ không thể chỉnh sửa dữ liệu của tháng này nữa.
    </p>
    
    <div style="text-align:center; margin:32px 0;">
        <a href="{{ $monthUrl }}" 
           style="display:inline-block; padding:14px 32px; background-color:#FF6A3D; color:#FFFFFF; text-decoration:none; border-radius:6px; font-weight:500; font-size:16px;">
            Kiểm tra
        </a>
    </div>
    
    <p style="margin:24px 0 0 0; font-size:14px; color:#6B7280;">
        Nếu bạn không thể click vào button, vui lòng copy link sau vào trình duyệt:<br>
        <a href="{{ $monthUrl }}" style="color:#FF6A3D; word-break:break-all;">{{ $monthUrl }}</a>
    </p>
</div>

@endsection

