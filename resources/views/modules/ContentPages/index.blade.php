@extends('layouts.app')

@section('content')



    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Content Pages</h4>
            </div>
            <div hidden>
                <a href="{{route('module.'.$moduleName.'.add')}}" class="btn btn-primary btn-icon">
                    <i data-feather="plus"></i>
                </a>
            </div>
        </div>
        <table data-table="mainGrid" data-url="{{route('module.'.$moduleName.'.datatable')}}" data-cols='{!! base64_encode($dataTableColumns) !!}' class="table table-hover">
            <thead>
            <tr>
                <th data-width="20%">ID</th>
                <th data-width="auto">Title</th>
                <th data-width="20%">Status</th>
                <th data-width="20%">Action</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>






@endsection

