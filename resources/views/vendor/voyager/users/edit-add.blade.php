@extends('voyager::master')

@section('page_title', __('voyager::generic.'.(isset($dataTypeContent->id) ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.(isset($dataTypeContent->id) ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
@stop

@section('content')

@php

$edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
@endphp


<style>
.color-picker {
     background-color: #fff;
     padding: 20px;
     border-radius: 8px;
     box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
     text-align: center;
 }
 .color-info {
     margin-top: 20px;
 }
 p {
     margin: 5px 0;
     font-size: 16px;
 }
</style>


    <div class="page-content container-fluid">
        <form class="form-edit-add" role="form"
              action="@if(!is_null($dataTypeContent->getKey())){{ route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) }}@else{{ route('voyager.'.$dataType->slug.'.store') }}@endif"
              method="POST" enctype="multipart/form-data" autocomplete="off">
            <!-- PUT Method if we are editing -->
            @if(isset($dataTypeContent->id))
                {{ method_field("PUT") }}
            @endif
            {{ csrf_field() }}

            <div class="row">
                <div class="col-md-8">
                    <div class="panel panel-bordered">
                    {{-- <div class="panel"> --}}
                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="panel-body">
                            
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter First Name"
                                       value="{{ old('first_name', $dataTypeContent->first_name ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter Last Name"
                                       value="{{ old('last_name', $dataTypeContent->last_name ?? '') }}">
                            </div>
                            
                            
                            <div class="form-group">
                                <label for="email">{{ __('voyager::generic.email') }}</label>
                                <input type="email" class="form-control" id="email" name="email"  placeholder="{{ __('voyager::generic.email') }}"
                                       value="{{ old('email', $dataTypeContent->email ?? '') }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"  placeholder=""
                                       value="{{ old('phone', $dataTypeContent->phone ?? '') }}">
                            </div>
                                                    
                            
                            <input type="hidden" class="form-control" id="device_token" name="device_token" value="{{ old('device_token', $dataTypeContent->device_token ?? '') }}">
                            <input type="hidden" class="form-control" id="company_id" name="company_id" value="{{ old('company_id', $dataTypeContent->company_id ?? '') }}">
                            
                            
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" placeholder="Enter position"
                                       value="{{ old('position', $dataTypeContent->position ?? '') }}">
                            </div>
                            
                             <input type="hidden" class="form-control" id="position" name="is_admin" placeholder="Enter is_admin"
                                       value="{{ old('is_admin', $dataTypeContent->is_admin ?? 0) }}">   
                                        <input type="hidden" class="form-control" id="position" name="payment_type" placeholder="Enter payment_type"
                                       value="{{ old('payment_type', $dataTypeContent->payment_type ?? 0) }}">   
                             

                            <div class="form-group">
                                <label for="password">{{ __('voyager::generic.password') }}</label>
                                @if(isset($dataTypeContent->password))
                                    <br>
                                    <small>{{ __('voyager::profile.password_hint') }}</small>
                                @endif
                                <input type="password" class="form-control" id="password" name="password"  autocomplete="new-password">
                            </div>

                            @can('editRoles', $dataTypeContent)
                               
                                  
                                    @php
                                        $dataTypeRows = $dataType->{(isset($dataTypeContent->id) ? 'editRows' : 'addRows' )};

                                        $row     = $dataTypeRows->where('field', 'user_belongsto_role_relationship')->first();
                                        $options = @$row->details;
                                    @endphp
                                   
                               
                                
                            @endcan
                            
                            
                             
                            @php
                            $options = [
                                "0" => "In Active",
                                "1" => "Active"
                            ];
                        @endphp
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">-- Select Status --</option>
                                @foreach($options as $key => $label)
                                    <option value="{{ $key }}" 
                                        {{ old('status', @$dataTypeContent->status ?? '') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                                                    
                            
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                  
                    
                      <div class="panel panel panel-bordered panel-warning">
                            <div class="panel panel-default">
                              <div class="panel-body" style="background: #545554;
    color: #fff;">Contractor Address</div>
                            </div>

                         <div class="panel-body" style="padding: 0px 20px 30px 20px;">
                            
                            <h3></h3>
                          <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="{{ old('company_name', $dataTypeContent->company->name ?? '') }}" required>
                            </div>
                        
                            <div class="form-group">
                                <label for="license_no">License No</label>
                                <input type="text" class="form-control" id="license_no" name="license_no" 
                                       value="{{ old('license_no', $dataTypeContent->company->license_no ?? '') }}">
                            </div>

                            <div class="form-group">
                                <label for="company_address">Company Address</label>
                                <input type="text" class="form-control" id="company_address" name="company_address" 
                                       value="{{ old('company_address', $dataTypeContent->company->address ?? '') }}" required>
                            </div>
                        
                        
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="{{ old('city', $dataTypeContent->userAddress->city ?? '') }}" required>
                            </div>
                        
                            <div class="form-group">
                                <label for="state_id">State</label>
                                <input type="text" class="form-control" id="state_id" name="state_id" 
                                       value="{{ old('state_id', $dataTypeContent->userAddress->state_id ?? '') }}" required>
                            </div>
                        
                            <div class="form-group">
                                <label for="zip_code">Zip Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="{{ old('zip_code', $dataTypeContent->userAddress->zip_code ?? '') }}" required>
                            </div>


                            
                        </div>
                    </div>
                      {{-- <div class="panel panel panel-bordered panel-warning">
                         <div class="panel panel-default" >
                              <div class="panel-body" style="background: #545554;
    color: #fff;" >Invoice Setting</div>
                            </div>
                        <div class="panel-body" style="padding: 0px 20px 30px 20px;"> --}}
                            
                            
{{--                            
                           <div class="form-group">
                                <label for="logo">Invoice Logo</label>
                                
                                <!-- Check if the logo exists in invoice settings -->
                                 @if(isset($dataTypeContent->invoiceSetting->logo))
                                    <img src="{{ $dataTypeContent->invoiceSetting->full_logo }}" 
                                         style="width:200px; height:auto; display:block; margin-bottom:10px;">
                                @else
                                    <!-- If no logo exists, display a placeholder or empty space -->
                                    <img src="{{ Voyager::image('no_image.png') }}" 
                                         style="width:200px; height:auto; display:block; margin-bottom:10px;">
                                @endif
                                
                                <!-- File input for logo upload -->
                                <input type="file" name="logo" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="color">Invoice Color</label>
                               
                                       
                                       <div class="color-picker">
                                        <div class="color-info">
                                            <p>HEX: <span id="hexValue">#{{ $dataTypeContent->invoiceSetting->color ?? 'ff0000' }}</span></p>
                                            
                                        </div>
                                    
                                       <input id="color" type="color" name="color" 
    value="{{ old('color', '#' . ($dataTypeContent->invoiceSetting->color ?? 'ff0000')) }}" 
    class="form-control">
                                    </div>
                                     
                            </div> --}}


                            
                        </div>
                    </div>
                    
                </div>
                
                
            </div>

            <button type="submit" class="btn btn-primary pull-right save">
                {{ __('voyager::generic.save') }}
            </button>
        </form>
        <div style="display:none">
            <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
            <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $('document').ready(function () {
            $('.toggleswitch').bootstrapToggle();
        });
    </script>
    <script>
     // Function to convert HEX to RGB
    

    // Update the RGB value when color input changes
    document.getElementById('color').addEventListener('input', function() {
        const hexValue = this.value;
        document.getElementById('hexValue').textContent = hexValue;

    });
</script>
@stop
