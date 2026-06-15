@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Add Card</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.create')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-row">

                <div class="form-group col-md-4">
                    <label for="first_name">Title <span class="tx-danger">*</span></label>
                    <input type="text" name="title" value="{{old('title')}}" required class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Please enter title">
                    @error('title')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="link">Link <span class="tx-danger">*</span></label>
                    <input type="url" name="link" value="{{old('link')}}" required class="form-control @error('link') is-invalid @enderror" id="link" placeholder="Please enter link">
                    @error('link')
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
                    <label for="short_detail">Short Detail</label>
                    <textarea name="short_detail" class="form-control" cols="30" rows="10">{{old('short_detail')}}</textarea>

                </div>


            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <button type="submit" value="1" name="saveClose" class="btn btn-warning">Save & Close</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>

@endsection
