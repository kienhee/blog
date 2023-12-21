@php
    $moduleName = 'user';
@endphp

@extends('layouts.admin.index')
@section('title', 'Quản lý ' . $moduleName)

@section('content')
    <x-breadcrumb parentName="Quản lý {{ $moduleName }}" parentLink="dashboard.user.index"
        childrenName="Danh sách {{ $moduleName }}" />
    <div class="card">
        <x-alert />
        <x-header-table tableName="Danh sách {{ $moduleName }}" link="dashboard.user.add" linkName="Tạo mới {{ $moduleName }}" />

        <form method="GET" class="mx-3 mb-4 mt-4">
            <div class="row ">
                <div class="col-md-6 col-lg-3 mb-2">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text" id="basic-addon-search31"><i class="bx bx-search"></i></span>
                        <input type="search" class="form-control" placeholder="Họ tên, Email, Số điện thoại"
                            name="keywords" value="{{ Request()->keywords }}">
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-2">
                    <select class="form-select" name="group_id">
                        <option value="">Nhóm người dùng</option>
                        @foreach (getAllGroups() as $group)
                            <option {{ Request()->group_id == $group->id ? 'selected' : '' }} value="{{ $group->id }}">
                                {{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3 mb-2">
                    <select class="form-select" name="status">
                        <option value="">Trạng thái</option>
                        <option value="active" {{ Request()->status == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ Request()->status == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động
                        </option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3 mb-2">
                    <select class="form-select" name="sort">
                        <option value="">Sắp xếp</option>
                        <option value="desc" {{ Request()->sort == 'desc' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="asc" {{ Request()->sort == 'asc' ? 'selected' : '' }}>Cũ nhất</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-12 mb-2 text-md-end">
                    <a href="{{ route('dashboard.user.index') }}" class="btn btn-outline-secondary">Đặt lại </a>
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                </div>

            </div>
        </form>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Họ tên</th>
                        <th>Nhóm người dùng</th>
                        <th>Số điện thoại</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Cài đặt</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($users->count() > 0)

                        @foreach ($users as $item)
                            <tr>
                                <td> <a href="{{ route('dashboard.user.edit', $item->id) }}" style="color: inherit"
                                        title="Click Read more"><strong>#{{ $item->id }}</strong>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('dashboard.user.edit', $item->id) }}" title="Click Read more"
                                        style="color: inherit" class="d-block"><strong>{{ $item->full_name }}</strong>
                                    </a>
                                    <small>{{ $item->email }}</small>
                                </td>

                                <td>
                                    {{ $item->group->name ?? '' }}
                                </td>
                                <td>
                                    {{ $item->phone_number }}
                                </td>
                                <td><span
                                        class="badge  me-1 {{ $item->deleted_at == null ? 'bg-label-success ' : ' bg-label-primary' }}">{{ $item->deleted_at == null ? 'Hoạt động' : 'Ngừng hoạt động' }}</span>
                                </td>

                                <td>
                                    <p class="m-0">{{ $item->created_at->format('d M Y') }}</p>
                                    <small>{{ $item->created_at->format('h:i A') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                                href="{{ route('dashboard.user.edit', $item->id) }}"><i
                                                    class="bx bx-edit-alt me-1"></i>
                                                Chỉnh sửa thông tin</a>

                                            @if (Auth::user()->id != $item->id)
                                                @if ($item->trashed() == 1)
                                                    <form class="dropdown-item"
                                                        action="{{ route('dashboard.user.restore', $item->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('delete')
                                                        <button class="btn p-0  w-100 text-start" type="submit">
                                                            <i class='bx bx-revision'></i>
                                                            Khôi phục hoạt động
                                                        </button>
                                                    </form>
                                                @endif
                                                <form class="dropdown-item"
                                                    action="{{ $item->trashed() ? route('dashboard.user.force-delete', $item->id) : route('dashboard.user.soft-delete', $item->id) }}"
                                                    method="POST"
                                                    @if ($item->trashed()) onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn không??')" @endif>
                                                    @csrf
                                                    @method('delete')
                                                    <button class="btn p-0  w-100 text-start" type="submit">
                                                        <i
                                                            class="bx {{ $item->trashed() ? 'bx-trash' : 'bx bxs-hand' }}  me-1"></i>
                                                        {{ $item->trashed() ? 'Xóa vĩnh viễn' : 'Tạm ngưng hoạt động' }}
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center">Không có dữ liệu!</td>
                        </tr>

                    @endif


                </tbody>
            </table>
        </div>
        <div class="mx-3 mt-3">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
@endsection
