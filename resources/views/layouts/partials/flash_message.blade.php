
@if ($message = Session::get('success'))
    <div class="alert alert-success" role="alert">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
    </div>
@endif


@if ($message = Session::get('error'))
    <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
    </div>
@endif


{{--
@if ($message = Session::get('error'))
    <div class="pos-absolute t-10 r-10">
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <h6 class="tx-inverse tx-14 mg-b-0 mg-r-auto">Notification</h6>
                <small>11 mins ago</small>
                <button type="button" class="ml-2 mb-1 close tx-normal" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                Hello, world! This is a toast message.
            </div>
        </div>
    </div>


@endif
@if ($message = Session::get('error'))
    <div class="pos-absolute t-10 r-10">
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <h6 class="tx-inverse tx-14 mg-b-0 mg-r-auto">Notification</h6>
                <small>11 mins ago</small>
                <button type="button" class="ml-2 mb-1 close tx-normal" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                Hello, world! This is a toast message.
            </div>
        </div>
    </div>


@endif


@if ($message = Session::get('warning'))
    <div class="alert alert-warning" role="alert">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
    </div>
@endif


@if ($message = Session::get('info'))
    <div class="alert alert-info" role="alert">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>{{ $message }}</strong>
    </div>
@endif


@if($errors->any())
    @foreach ($errors->all() as $error)

        <div class="alert alert-danger" role="alert">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $error }}</strong>
        </div>
    @endforeach
@endif
--}}
