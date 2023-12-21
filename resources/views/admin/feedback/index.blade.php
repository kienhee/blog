```php
@extends('layouts.admin.index')
@section('title', 'Chứng Thực')
@section('content')

    <x-breadcrumb parentName="Chứng Thực" parentLink="dashboard.feedback.index" childrenName="Tất Cả Chứng Thực" />
    <div class="card">
        <x-alert />
        <x-header-table tableName="Chứng Thực" link="dashboard.feedback.add" linkName="Thêm chứng thực mới" />
        <div class="card-body">

            <div class="table-responsive text-nowrap">
                <table class="table table-bordered mb-3">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Ảnh Đại Diện</th>
                            <th>Tên</th>
                            <th>Nghề Nghiệp</th>
                            <th>Chứng Thực</th>
                            <th>Cài Đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($feedbacks as $feedback)
                            <tr>
                                <td class="text-center">

                                    <a class="text-dark" title="Chi tiết"
                                        href="{{ route('dashboard.feedback.edit', $feedback->id) }}">
                                        {{ $feedback->id }}</a>
                                </td>
                                <td class="text-center">
                                    <img src="{{ $feedback->avatar ?? '/images/no-img.png' }}"
                                        style="width:80px;height:80px; object-fit:cover;border-radius:4px"
                                        alt="Ảnh Đại Diện">
                                </td>
                                <td> <a title="Xem chứng thực" class="text-dark" target="_blank"><strong>
                                            {{ $feedback->name }}</strong></a></h3>
                                </td>
                                <td>
                                    {{ $feedback->career }}
                                </td>
                                <td>
                                    {{ $feedback->feedback }}
                                </td>



                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('dashboard.feedback.edit', $feedback->id) }}"><i
                                                    class="bx bx-edit-alt me-1"></i> Xem Thêm</a>
                                            <form class="dropdown-item" style="cursor: pointer"
                                                action="{{ route('dashboard.feedback.delete', $feedback->id) }}" method="POST"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa chứng thực này không?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn p-0" type="submit">
                                                    <i class="bx bx-trash me-1"></i><span>Xóa Chứng Thực</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $feedbacks->links() }}
            </div>
        </div>
    </div>
@endsection
```