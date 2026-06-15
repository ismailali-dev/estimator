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
                    <label for="title">Title <span class="tx-danger">*</span></label>
                    <input type="text" name="title" value="{{old('title')}}" required class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Please enter title">
                    @error('title')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="description">Description <span class="tx-danger" hidden>*</span></label>
                    <div class="basic-quill" data-id="#description" data-placeholder="Description" data-name="description">
                        
                    </div>
                </div>

            </div>

            <div class="form-row">
                <div class="form-group col-md-12 mb-0">
                    <div style="height: 80px; display: block"></div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="submit" value="1" name="saveClose" class="btn btn-warning">Save & Close</button>
                    <button type="reset" class="btn btn-light">Reset</button>
                </div>
            </div>

        </form>
    </div>

@endsection
