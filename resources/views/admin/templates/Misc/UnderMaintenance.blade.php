@extends('layouts.admin.index')
@section('title',"Under Maintenance")
@section('content')
    <div class="misc-wrapper">
        <h2 class="mb-2 mx-2">Under Maintenance!</h2>
        <p class="mb-4 mx-2">Sorry for the inconvenience but we're performing some maintenance at the moment</p>
        <a href="javascript:void(0);" onclick="window.history.back()" class="btn btn-primary">Back to home</a>
        <div class="mt-4">
            <img src="{{ asset('admin/assets') }}/img/illustrations/girl-doing-yoga-light.png" alt="girl-doing-yoga-light"
                width="500" class="img-fluid" data-app-dark-img="illustrations/girl-doing-yoga-dark.png"
                data-app-light-img="illustrations/girl-doing-yoga-light.png" />
        </div>
    </div>
@endsection
