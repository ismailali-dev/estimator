@extends('layouts.app')
@push('style')
    <link href="{{asset('assets/lib/tabs-accordian/custom-accordions.css')}}" rel="stylesheet">

@endpush

@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">{{$contractor->full_name}}  Estimates</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')
        <div class="widget-content widget-content-area">
            <table id="zero-config" class="table dt-table-hover simple-dt">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Scope</th>
                    <th>Customer</th>

                    <th class="no-content" style="width:90px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($estimates as $estimate)
                    <tr>
                        <td>{{$estimate["date"]}}</td>
                        <td>{{$estimate["scope"]}}</td>
                        <td>{{$estimate["customer_name"]}}</td>
                        <td>
                            <a href="{{$estimate["report"]}}"><i class="fas fa-download"></i> </a>&nbsp;&nbsp;&nbsp;&nbsp;
                            <a href="{{$estimate["edit"]}}"><i class="fas fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                            <a href="javascript:" onclick="{{$estimate["delete"]}}" style="color: red!important;"><i class="fas fa-trash"></i></a>

                        </td>

                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    </div>

@endsection



