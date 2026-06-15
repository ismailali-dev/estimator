@extends('layouts.app')

@section('content')



    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Push Notifications</h4>
            </div>
            <div>
                <a href="{{route('module.'.$moduleName.'.add')}}" class="btn btn-primary btn-icon">
                    <i data-feather="plus"></i>
                </a>
            </div>
        </div>
        <table data-table="mainGrid" data-url="{{route('module.'.$moduleName.'.datatable')}}" data-cols='{!! base64_encode($dataTableColumns) !!}' class="table table-hover">
            <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="20%">Title</th>
                <th width="50%">Message</th>
                <th width="10%">Contractors</th>
                <th width="15%">Date</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>






@endsection

