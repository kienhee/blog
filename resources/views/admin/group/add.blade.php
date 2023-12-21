    @extends('layouts.admin.index')
@section('title', 'Thêm mới nhóm')

@section('content')
    <x-breadcrumb parentName="Nhóm" parentLink="dashboard.group.index" childrenName="Thêm mới nhóm" />
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <x-alert />
                <x-header-table tableName="Thêm mới nhóm" link="dashboard.group.index" linkName="Tất cả nhóm" />
                <!-- Tài khoản -->
                <div class="card-body">
                    <form id="formAccountSettings" action="{{ route('dashboard.group.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="name" class="form-label">Tên nhóm: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror " type="text"
                                    id="name" name="name" value="{{ old('name') }}"
                                    placeholder="VD: ADMIN, MANAGER,..." autofocus />
                                @error('name')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Thêm mới nhóm</button>
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </form>
                </div>
                <!-- /Tài khoản -->
            </div>
        </div>
    </div>
@endsection
