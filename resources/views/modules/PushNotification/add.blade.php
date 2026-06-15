@extends('voyager::master')
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">Send Push Notification</h4>
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




            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="short_detail">Message <span class="tx-danger">*</span></label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" cols="30" rows="5">{{old('message')}}</textarea>
                    @error('message')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>


            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="short_detail">Contractors <span class="tx-danger">*</span></label>
                    <div class="row">
                        @foreach($contractors as $contractor)
                            <div class="col-3">
                                <div class="custom-control custom-checkbox">
                                    @php($isChecked = false)
                                    @if(old('contractor'))
                                        @if(in_array($contractor->id, old('contractor')))
                                            @php($isChecked = true)
                                        @endif
                                    @endif

                                    <input type="checkbox" name="contractor[]" {{(($isChecked) ? 'checked' : '')}} value="{{$contractor->id}}" class="custom-control-input" id="contractor_{{$contractor->id}}">
                                    <label class="custom-control-label" for="contractor_{{$contractor->id}}">{{$contractor->full_name}}</label>
                                </div>
                            </div>

                        @endforeach
                    </div>
                    @error('contractor')
                    <div class="row">
                        <div class="col-6">
                            <div class="tx-danger">{{ $message }}</div>
                        </div>
                    </div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <button type="submit" value="1" name="saveClose" class="btn btn-warning">Save & Close</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>

@endsection
