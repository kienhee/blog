@extends('layouts.admin.master')
@section('content')
    <div class="table-responsive">
        <table class="table table-bordered" id="contactTable">
            <thead>
                <tr>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Chủ đề</th>
                    <th>Tin nhắn</th>
                </tr>
            </thead>
        </table>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#contactTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{!! route('dashboard.ajaxGetDataContact') !!}",
                columns: [
                    {
                        data: 'name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'subject'
                    },
                    {
                        data: 'message'
                    },

                ]
            });
        });
    </script>
@endsection
