@extends('layouts.app')
@push('style')
    <script>
        var isShow = false;
    </script>
@endpush

@section('content')
    @include('layouts.partials.flash_message')
    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Contractors</h4>
            </div>
        </div>
        <table data-table="mainGrid" data-url="{{route('module.'.$moduleName.'.datatable')}}" data-cols='{!! base64_encode($dataTableColumns) !!}' class="table table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Company</th>
                <th>Email</th>
                <th>Suppliers</th>
                <th>Customers</th>
                <th>Estimates</th>
                <th>Templates</th>
                <th>Codes</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
