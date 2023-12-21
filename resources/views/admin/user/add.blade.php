@php
    $moduleName = 'user';
@endphp

@extends('layouts.admin.index')
@section('title', 'Tạo mới ' . $moduleName)

@section('content')
    <x-breadcrumb parentName="Quản lý {{ $moduleName }}" parentLink="dashboard.user.index"
        childrenName="Tạo mới {{ $moduleName }}" />
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <x-alert />
                <x-header-table tableName="Tạo mới {{ $moduleName }}" link="dashboard.user.index"
                    linkName="Danh sách {{ $moduleName }}" />
                <div class="card-body">
                    <form id="formAccountSettings" action="{{ route('dashboard.user.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="full_name" class="form-label">Họ và tên: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control @error('full_name') is-invalid @enderror " type="text"
                                    id="full_name" name="full_name" value="{{ old('full_name') }}" placeholder="John Doe"
                                    autofocus />
                                @error('full_name')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="email" class="form-label">E-mail: <span class="text-danger">*</span></label>
                                <input class="form-control @error('email') is-invalid @enderror" type="text"
                                    id="email" name="email" value="{{ old('email') }}"
                                    placeholder="john.doe@example.com" />
                                @error('email')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="group" class="form-label">Nhóm người dùng: <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('group_id') is-invalid @enderror" name="group_id"
                                    id="group">
                                    <option value="">Vui lòng chọn</option>
                                    @foreach (getAllGroups() as $group)
                                        <option {{ old('group_id') == $group->id ? 'selected' : '' }}
                                            value="{{ $group->id }}">
                                            {{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="phone_number">Số điện thoại: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control @error('phone_number') is-invalid @enderror" type="text"
                                    id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                    placeholder="000-000-0000">
                                @error('phone_number')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-password-toggle mb-3 col-md-6">
                                <label class="form-label" for="password">Mật khẩu: <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                    <span id="basic-default-password2" class="input-group-text cursor-pointer"><i
                                            class="bx bx-hide"></i></span>
                                </div>
                                @error('password')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-password-toggle mb-3 col-md-6">
                                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu: <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control @error('password_confirmation') is-invalid @enderror"
                                        id="password_confirmation" name="password_confirmation"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                    <span id="basic-default-password2" class="input-group-text cursor-pointer"><i
                                            class="bx bx-hide"></i></span>
                                </div>
                                @error('password_confirmation')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Tạo mới {{ $moduleName }}</button>
                            <button type="reset" class="btn btn-outline-secondary">Đặt lại</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
