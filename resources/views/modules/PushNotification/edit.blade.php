@extends('layouts.app')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Edit Card</h4>
            </div>

        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="put" />
            <input type="hidden" name="id" value="{{$data['id']}}" />
            <div class="form-row">

                <div class="form-group col-md-4">
                    <label for="title">Title <span class="tx-danger">*</span></label>
                    <input type="text" name="title" value="{{$data['title']}}" required class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Please enter title" required>
                    @error('title')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="short_detail">Short Detail</label>
                    <textarea name="short_detail" class="form-control" cols="30" rows="5">{{$data['short_detail']}}</textarea>

                </div>


            </div>


            <button type="submit" class="btn btn-primary">Save</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>

@endsection
