@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Edit Profile Settings</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="put" />
            <input type="hidden" name="id" value="{{$data['id']}}" />
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="inputEmail4">Name <span class="tx-danger">*</span></label>
                    <input type="text" name="name" value="{{$data['name']}}" required class="form-control @error('name') is-invalid @enderror" id="inputEmail4" placeholder="Please enter supplier name">
                    @error('name')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>


            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Phone <span class="tx-danger">*</span></label>
                    <input type="number" name="phone" value="{{$data['phone']}}" class="form-control @error('phone') is-invalid @enderror" id="inputEmail4" placeholder="Please enter phone number">
                    @error('phone')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Email <span class="tx-danger">*</span></label>
                    <input type="text" name="email" value="{{$data['email']}}" class="form-control @error('phone') is-invalid @enderror" id="inputEmail4" placeholder="Please enter email address">
                    @error('email')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>
            <hr>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Change Password</label>
                    <input type="password" name="password" class="form-control " id="inputEmail4" placeholder="Please enter password">
                    @error('password')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Confirm Password</label>
                    <input type="password" name="password_confirmation"  class="form-control " id="inputEmail4" placeholder="Please enter confirm password">
                    @error('password_confirmation')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>


@endsection
