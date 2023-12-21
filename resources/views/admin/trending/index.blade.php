@extends('layouts.admin.index')
@section('title', 'Trendings')

@section('content')
<div class="card">
    <x-alert />
    <x-header-table tableName="All trendings" link="dashboard.trending.add" linkName="Add trend" />

    <hr class="my-0" />


    <div class="table-responsive text-nowrap mt-4">
        <table class="table">
            <thead>
                <tr>
                    <th class="px-1 text-center" style="width: 50px">#ID</th>
                    <th class="px-1 text-center" style="width: 50px"></th>
                    <th>Project name</th>
                    <th class="px-1 text-center" style="width: 50px">position</th>
                    <th class="px-5 text-center" style="width: 50px">setting</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @if ($trendings->count() > 0)

                @foreach ($trendings as $item)
                <tr>
                    <td> <a href="{{ route('dashboard.trending.edit', $item->project->id) }}" title="Click xem thêm"
                            style="color: inherit"><strong>#{{ $item->project->id }}</strong>
                        </a>
                    </td>
                    <td class="px-0 text-center">
                        <img src="{{ $item->project->image }}" alt="Ảnh"
                            class=" object-fit-cover border rounded w-px-40 h-px-40" style="object-fit: cover">
                    </td>
                    <td>
                        <a href="{{ route('dashboard.trending.edit', $item->id) }}" style="color: inherit    "
                            title="Click Read more" class="d-block">
                            <strong>
                                {{ $item->project->name }}
                            </strong>
                        </a>
                        <small>Category: {{ $item->project->category->name }}</small>
                    </td>

                    <td class="text-center">
                        <strong class="text-success">{{ $item->position}}</strong>
                    </td>
                    <td class="text-center">
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('dashboard.trending.edit', $item->id) }}"><i
                                        class="bx bx-edit-alt me-1"></i>
                                    Read more</a>

                                <form class="dropdown-item" action="{{ route('dashboard.trending.delete', $item->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete it permanently?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn p-0  w-100 text-start" type="submit">
                                        <i class="bx bx-trash  me-1"></i>
                                        Permanently deleted
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
