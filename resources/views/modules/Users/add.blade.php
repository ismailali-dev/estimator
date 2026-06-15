@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Add User</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.create')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-row">

                <div class="form-group col-md-4">
                    <label for="first_name">First Name <span class="tx-danger">*</span></label>
                    <input type="text" name="first_name" value="{{old('first_name')}}" required class="form-control @error('first_name') is-invalid @enderror" id="first_name" placeholder="Please enter first name">
                    @error('first_name')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="last_name">Last Name <span class="tx-danger">*</span></label>
                    <input type="text" name="last_name" value="{{old('last_name')}}" required class="form-control @error('last_name') is-invalid @enderror" id="last_name" placeholder="Please enter last name">
                    @error('last_name')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="form-group col-md-4">
                    <label for="inputEmail4">Type <span class="tx-danger">*</span></label>
                    <select class="form-control select2  @error('type') is-invalid @enderror" name="type" required>
                        <option label="Select Company"></option>
                        @foreach ($types as $value)
                            @if($value != 'APP-USER')
                            <option value="{{$value}}">{{ucfirst($value)}}</option>
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
                    <label for="inputEmail4">Phone <span class="tx-danger">*</span></label>
                    <input type="number" name="phone" value="{{old('phone')}}" class="form-control @error('phone') is-invalid @enderror" id="inputEmail4" required placeholder="Please enter phone number">
                    @error('phone')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Email <span class="tx-danger">*</span></label>
                    <input type="text" name="email" value="{{old('email')}}" class="form-control @error('phone') is-invalid @enderror" id="inputEmail4" placeholder="Please enter email address">
                    @error('email')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>
            <hr>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Password <span class="tx-danger">*</span></label>
                    <input type="password" name="password" class="form-control " id="inputEmail4" placeholder="Please enter password">
                    @error('password')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="inputEmail4">Confirm Password <span class="tx-danger">*</span></label>
                    <input type="password" name="password_confirmation"  class="form-control " id="inputEmail4" placeholder="Please enter confirm password">
                    @error('password_confirmation')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <button type="submit" value="1" name="saveClose" class="btn btn-warning">Save & Close</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>

@endsection
