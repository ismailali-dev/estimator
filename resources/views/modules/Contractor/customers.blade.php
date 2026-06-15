@extends('layouts.app')
@push('style')
    <link href="{{asset('assets/lib/tabs-accordian/custom-accordions.css')}}" rel="stylesheet">

@endpush

@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">{{$contractor->full_name}}  Customers</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')
        <div class="widget-content widget-content-area">
            <table id="zero-config" class="table dt-table-hover simple-dt">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip Code</th>
                </tr>
                </thead>
                <tbody>
                @foreach($customers as $customer)
                    <tr>
                        <td>{{$customer["full_name"]}}</td>
                        <td>{{$customer["phone"]}}</td>
                        <td>{{$customer["email"]}}</td>
                        <td>{{$customer["address"]}}</td>
                        <td>{{$customer["city"]}}</td>
                        <td>{{$customer["state"]}}</td>
                        <td>{{$customer["zip_code"]}}</td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    </div>

@endsection



