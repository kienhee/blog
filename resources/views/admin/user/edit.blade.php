@php
    $moduleName = 'user';
@endphp

@extends('layouts.admin.index')
@section('title', 'Cập nhật ' . $moduleName)
@section('content')
    <x-breadcrumb parentName="Quản lý {{ $moduleName }}" parentLink="dashboard.user.index"
        childrenName="Cập nhật {{ $moduleName }}" />
    <div class="row">
        <div class="col-md-12">
            <form method="POST" action="{{ route('dashboard.user.update', $user->id) }}" enctype="multipart/form-data">
                <div class="card mb-4">
                    <x-alert />
                    <x-header-table tableName="Cập nhật {{ $moduleName }}" link="dashboard.user.index"
                        linkName="Danh sách {{ $moduleName }}" />
                    <div class="card-body">

                        <div class="d-flex align-items-start align-items-sm-center gap-4">
                            <img src="{{ $user->avatar ?? asset('images/avatar-default.png') }}" alt="user-avatar"
                                class="d-block rounded " style="object-fit:cover" height="100" width="100"
                                id="uploadedAvatar" />
                            <div class="button-wrapper">
                                <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                                    <span class="d-none d-sm-block">Tải lên</span>
                                    <i class="bx bx-upload d-block d-sm-none"></i>
                                    <input type="file" id="upload" class="account-file-input" hidden name="avatar"
                                        accept="image/png, image/jpeg" />
                                </label>
                            </div>
                        </div>

                    </div>
                    <hr class="my-0" />
                    <div class="card-body">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="full_name" class="form-label">Họ và tên: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="full_name" name="full_name"
                                    value="{{ $user->full_name }}" />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="" class="form-label">E-mail: </label>
                                <input class="form-control" type="text" disabled placeholder="john.doe@example.com"
                                    value="{{ $user->email }}" />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="group" class="form-label">Nhóm người dùng: <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" name="group_id" id="group">
                                    <option>Vui lòng chọn</option>
                                    @foreach (getAllGroups() as $group)
                                        <option {{ $user->group_id == $group->id ? 'selected' : '' }}
                                            value="{{ $group->id }}">
                                            {{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="phone_number">Số điện thoại: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="phone_number" name="phone_number"
                                    value="{{ $user->phone_number }}" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Cập nhật {{ $moduleName }}</button>
                            <button type="reset" class="btn btn-outline-secondary">Đặt lại</button>
                        </div>
                    </div>
            </form>
        </div>
    </div>
    </div>
    <script>
        let imgInp = document.getElementById('upload');
        let preview = document.getElementById('uploadedAvatar');
        imgInp.onchange = evt => {
            const [file] = imgInp.files
            if (file) {
                preview.src = URL.createObjectURL(file)
            }
        }
    </script>
@endsection
