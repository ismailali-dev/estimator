@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Contractor Detail</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="put" />
            <input type="hidden" name="id" value="{{$user->id}}" />
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="first_name">First Name <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->first_name}}</p>
                </div>
                <div class="form-group col-md-4">
                    <label for="last_name">Last Name <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->last_name}}</p>
                </div>
                <div class="form-group col-md-4">
                    <label for="phone_no" >Contact Number <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->phone}}</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="company_name">Company Name <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->company_name}}</p>
                </div>
                <div class="form-group col-md-4">
                    <label for="company_address">Company Address <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->company_address}}</p>
                </div>
                <div class="form-group col-md-4">
                    <label for="license_no" >License Number <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{$user->license_no}}</p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="job_type">Job Type <span class="text-default">:</span></label>
                    <p class="text-capitalize"> {{ ($user->JobTypes()->exists() ? $user->JobTypes->title : '')}}</p>
                </div>
                <div class="form-group col-md-2">
                    <label for="company_address">Status <span class="text-default">:</span></label>
                    <select class="form-control selectpicker" name="status">
                        <option {{$user->status ? 'selected' : ''}} value="1">Enable</option>
                        <option {{$user->status ? '' : 'selected'}} value="0">Disable</option>
                    </select>
                </div>

            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>

@endsection
