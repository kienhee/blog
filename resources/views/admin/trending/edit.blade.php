@extends('layouts.admin.index')
@section('title', 'Update trending')


@section('content')
<x-breadcrumb parentName="Trendings" parentLink="dashboard.trending.index" childrenName="Update trending" />

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Update trending" link="dashboard.trending.index" linkName="All trendings " />
            <!-- Account -->
            <div class="card-body">
                <form action="{{ route('dashboard.trending.update',$trending->id) }}" method="POST">
                    @csrf
                    @method('put')
                    <div class="row ">
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Project name: <span class="text-danger">*</span></label>
                            <select id="select-multiple" placeholder="Choose project" data-search="true"
                                data-silent-initial-value-set="true" disabled="true">
                                @foreach (getAllProjects() as $item)
                                <option @if ($trending->project_id == $item->id)
                                    @selected(true)
                                    @endif value="{{ $item->id }}">{{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="position" class="form-label">Position: <span
                                    class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="position" name="position"
                                value="{{ old('position')??$trending->position }}" />
                            @error('position')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">Update position</button>
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>
            </div>
            <!-- /Account -->
        </div>
    </div>
</div>
@endsection