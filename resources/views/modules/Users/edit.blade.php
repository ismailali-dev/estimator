@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Edit User</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="put" />
            <input type="hidden" name="id" value="{{$data['id']}}" />
            <div class="form-row">

                <div class="form-group col-md-4">
                    <label for="first_name">First Name <span class="tx-danger">*</span></label>
                    <input type="text" name="first_name" value="{{$data['first_name']}}" required class="form-control @error('first_name') is-invalid @enderror" id="first_name" placeholder="Please enter first name" required>
                    @error('first_name')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="last_name">Last Name <span class="tx-danger">*</span></label>
                    <input type="text" name="last_name" value="{{$data['last_name']}}" required class="form-control @error('last_name') is-invalid @enderror" id="last_name" placeholder="Please enter last name" required>
                    @error('last_name')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="type">Type <span class="tx-danger">*</span></label>
                    <select class="form-control select2  @error('type') is-invalid @enderror" id="type" name="type">
                        @foreach ($types as $value)
                            @if($value != 'APP-USER')
                            <option value="{{$value}}" {{$data['type'] == $value ? 'selected' : ''}}>{{ucfirst($value)}}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('type')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phone">Phone <span class="tx-danger">*</span></label>
                    <input type="number" name="phone" value="{{$data['phone']}}" class="form-control @error('phone') is-invalid @enderror" id="phone" required placeholder="Please enter phone number">
                    @error('phone')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="email">Email <span class="tx-danger">*</span></label>
                    <input type="text" name="email" value="{{$data['email']}}" class="form-control @error('phone') is-invalid @enderror" id="email" placeholder="Please enter email address">
                    @error('email')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>
            <hr>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="password">Change Password</label>
                    <input type="password" name="password" class="form-control " id="password" placeholder="Please enter password">
                    @error('password')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation"  class="form-control " id="password_confirmation" placeholder="Please enter confirm password">
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
