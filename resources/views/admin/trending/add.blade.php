@extends('layouts.admin.index')
@section('title', 'Add new trending')


@section('content')
<x-breadcrumb parentName="Trendings" parentLink="dashboard.trending.index" childrenName="Add new trending" />

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Add new trending" link="dashboard.trending.index" linkName="All trendings " />
            <!-- Account -->
            <div class="card-body">
                <form action="{{ route('dashboard.trending.store') }}" method="POST">
                    @csrf
                    <div class="row ">
                        <div class="mb-3 col-md-6">
                            <label for="project_id" class="form-label">Project name: <span
                                    class="text-danger">*</span></label>
                            <select id="select-multiple" class="@error('project_id') is-invalid @enderror"
                                name="project_id" placeholder="Choose project" data-search="true"
                                data-silent-initial-value-set="true">
                                @foreach (getAllProjects() as $item)
                                @if (!trendExist($trending,$item->id))
                                <option value="{{ $item->id }}">{{ $item->name }}
                                </option>
                                @endif

                                @endforeach
                            </select>
                            @error('project_id')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="position" class="form-label">Position: <span
                                    class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="position" name="position"
                                value="{{ old('position') }}" />
                            @error('position')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">Add new trending</button>
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>
            </div>
            <!-- /Account -->
        </div>
    </div>
</div>
@endsection
