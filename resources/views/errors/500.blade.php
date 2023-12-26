@extends('layouts.client.index')
@section('title', '404')
@section('content')
    <div id="notfound">
        <div class="notfound">
            <div class="notfound-404">
                <h1>500</h1>
            </div>
            <h2>LỖI MÁY CHỦ!</h2>
            <p>Chúng tôi không thể tìm hiểu chuyện gì đang xảy ra!.
            </p>
            <a href="javascript:void(0)" onclick="window.history.back()"  class="btn btn-primary  text-white rounded-pill fw-medium">QUAY LẠI</a>
        </div>
    </div>
@endsection
