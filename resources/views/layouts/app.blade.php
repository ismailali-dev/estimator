<!DOCTYPE html>
<html lang="en">
@include('layouts.partials.head')

<body>
@include('layouts.partials.sidebar')
<div class="content ht-100v pd-0" id="app">

    <div class="content ht-100v pd-0">
        @include('layouts.partials.header')
        @isNotSubscribed
        <div class="alert alert-danger mg-b-0" role="alert" style="border: unset !important;border-radius: unset!important;">
           <center>Hello! your subscription is expired so you cannot access <strong>Transactions and Reporting</strong> module</center>
        </div>
        @endisNotSubscribed
        <div class="content-body">
            <div class="container pd-x-0">

                <div class="modal fade" id="subscriptionExpiredPopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">

                            <div class="modal-body" style="font-size: 15px">
                                <span style="font-size: 30px; font-weight: 600">Hello!</span> <br>Your subscription is expired so you cannot access <strong>Transactions and Reporting</strong> module
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <a href="tel:+923171015636" class="btn btn-success"><i data-feather="phone-call"></i> Call Us</a>
                            </div>
                        </div>
                    </div>
                </div>
                @include('layouts.partials.breadcrum')

                @yield('content')
            </div>
        </div>
    </div>
</div>
@include('layouts.partials.btmjs')
</body>
</html>
