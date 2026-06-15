@extends('layouts.app')
@push('style')
    <link href="{{asset('assets/lib/tabs-accordian/custom-accordions.css')}}" rel="stylesheet">

@endpush

@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">{{$contractor->full_name}}  Suppliers</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')
        <div class="widget-content widget-content-area">
            @if(count($data) > 1231321323)
            <div id="toggleAccordion">
                @foreach($data as $supplier)
                    <div class="card">
                        <div class="card-header" id="{{'headingOne'.$supplier["id"]}}">
                            <section class="mb-0 mt-0">
                                <div role="menu" class="collapsed" data-toggle="collapse" data-target="#{{'defaultAccordion'.$supplier["id"]}}" aria-expanded="true" aria-controls="{{'defaultAccordion'.$supplier["id"]}}">
                                    {{$supplier["full_name"]}}  <div class="icons"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                                </div>
                            </section>
                        </div>

                        <div id="{{'defaultAccordion'.$supplier["id"]}}" class="collapse" aria-labelledby="{{'headingOne'.$supplier["id"]}}" data-parent="#toggleAccordion">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-light mb-4">
                                        <tbody>
                                        <tr>
                                            <td>Phone:</td>
                                            <td>{{$supplier["phone"]}}</td>
                                        </tr>
                                        <tr>
                                            <td>Email</td>
                                            <td>{{$supplier["email"]}}</td>
                                        </tr>
                                        <tr>
                                            <td>Address</td>
                                            <td>{{$supplier["address"]}}</td>
                                        </tr>
                                        <tr>
                                            <td>State</td>
                                            <td>{{$supplier["state"]}}</td>
                                        </tr>
                                        <tr>
                                            <td>City</td>
                                            <td>{{$supplier["city"]}}</td>
                                        </tr>
                                        <tr>
                                            <td>Zip Code</td>
                                            <td>{{$supplier["zip_code"]}}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @endif

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
                    @foreach($data as $supplier)
                    <tr>
                        <td>{{$supplier["full_name"]}}</td>
                        <td>{{$supplier["phone"]}}</td>
                        <td>{{$supplier["email"]}}</td>
                        <td>{{$supplier["address"]}}</td>
                        <td>{{$supplier["city"]}}</td>
                        <td>{{$supplier["state"]}}</td>
                        <td>{{$supplier["zip_code"]}}</td>
                    </tr>
                    @endforeach
                    
                    </tbody>
                </table>
        </div>
    </div>

@endsection



