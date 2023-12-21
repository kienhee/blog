@extends('layouts.admin.index')
@section('title', 'Thẻ bài viết')

@section('content')
    <x-breadcrumb parentName="Thẻ bài viết" parentLink="dashboard.tag.index" childrenName="Tất cả thẻ bài viết" />
    <div class="card">
        <x-alert />
        <x-header-table tableName="Tất cả thẻ bài viết" link="dashboard.tag.add" linkName="Thêm thẻ mới " />

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Tên</th>
                        <th>Ngày tạo</th>
                        <th>Cài đặt</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($tags->count() > 0)

                        @foreach ($tags as $item)
                            <tr>
                                <td><i class="fab fa-angular fa-lg text-danger "></i> <a
                                        href="{{ route('dashboard.tag.edit', $item->id) }}"><strong>#{{ $item->id }}</strong>
                                    </a>
                                </td>
                                <td>{{ $item->name }}</td>

                                <td>
                                    {{ optional($item->created_at)->format('d-m-Y') ?? '' }}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('dashboard.tag.edit', $item->id) }}"><i
                                                    class="bx bx-edit-alt me-1"></i>
                                                Sửa thông tin</a>
                                            <form class="dropdown-item"
                                                action="{{ route('dashboard.tag.delete', $item->id) }}" method="POST"
                                                onsubmit="return confirm('Bạn chắc chắn muốn xóa vĩnh viễn?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn p-0  w-100 text-start" type="submit">
                                                    <i class="bx bx-trash  me-1"></i>
                                                    Xóa vĩnh viễn
                                                </button>
                                            </form>


                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center">Không có dữ liệu!</td>
                        </tr>

                    @endif


                </tbody>
            </table>
        </div>

    </div>
@endsection
