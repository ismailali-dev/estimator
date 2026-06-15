@extends('voyager::master')

@section('css')
    <style>
        .user-email {
            font-size: .85rem;
            margin-bottom: 1.5em;
        }
    </style>
@stop

@section('content')
    <div ></div>
    <div style="height:160px; display:block; width:100%"></div>
    <div style="position:relative; z-index:9; text-align:center;    width: 500px;
    margin: 0 auto;
    background: #e4e4e4;
    padding: 67px;
    border-radius: 10px;" >
        <img src="@if( !filter_var(Auth::user()->avatar, FILTER_VALIDATE_URL)){{ Voyager::image( Auth::user()->avatar ) }}@else{{ Auth::user()->avatar }}@endif"
             class="avatar"
             style="border-radius:50%; width:150px; height:150px; border:5px solid #fff;"
             alt="{{ Auth::user()->name }} avatar">
        <h4 style="    margin-top: 25px;
    font-size: 30px;">{{ ucwords(Auth::user()->first_name) }}</h4>
        <div class="user-email text-muted" style="font-size: 17px;
    color: #000;
}">{{ ucwords(Auth::user()->email) }}</div>
        <p>{{ Auth::user()->bio }}</p>
        @if ($route != '')
            <a href="{{ $route }}" class="btn btn-primary">{{ __('voyager::profile.edit') }}</a>
        @endif
    </div>
@stop
