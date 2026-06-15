@extends('voyager::auth.master')

@section('content')

<style>

    .form-group {
        position: relative;
    }
    .btn-view-password {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translate(0, -50%);
        margin: 0;
        z-index: 2;
    }
    .developedByText {
        position: fixed;
        bottom: 20px;
        right: 36px;
        font-size: 16px;
        z-index: 10;
    }
    .developedByText a {
        border-bottom: 1px solid #337ab7;
    }
    .remember-me-text {
        position: relative;
        padding-left: 30px;
        color: #06080a;
        font-size: 16px;
    }
    .remember-me-text input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    .remember-me-text .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 25px;
        width: 25px;
        background-color: #eee;
    }
    .remember-me-text input:checked ~ .checkmark {
        background-color: #d4e155;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Create the checkmark/indicator (hidden when not checked) */
    .remember-me-text .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    /* Show the checkmark when checked */
    .remember-me-text input:checked ~ .checkmark:after {
        display: block;
    }

    /* Style the checkmark/indicator */
    .remember-me-text .checkmark:after {
        left: 10px;
        top: 7px;
        width: 5px;
        height: 10px;
        border: solid #000000;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
    }
    @media (max-width: 768px) {
        .login-container {
            padding: 0 40px !important;
        }
        .login-container img.img-responsive {
            max-width: 200px !important;
        }
        .login-sidebar {
            display: flex !important;
            align-content: center !important;
            justify-content: center;
            flex-direction: column;
        }
        .developedByText {
            width: 100%;
            right: auto;
            text-align: center;
        }
    }

</style>

<div class="login-container">



    <div class="row justify-content-center">


        <div class="col-md-6 col-md-offset-2">



            <div>

                <?php  $admin_logo_img = Voyager::setting('admin.login_logo', ''); ?>
                @if($admin_logo_img)
                <img class="img-responsive" src="{{ url("public/storage/".$admin_logo_img) }}" alt="Logo Icon" style="width:300px;margin: auto;">
                @else
                <h1>EZ Estimater</h1>
                @endif
                <h1 style="color:#000;font-weight:bold;text-align:center">Login</h1>
            </div>



            <form action="{{ route('voyager.login') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-group form-group-default" id="emailGroup">
                    <label>{{ __('voyager::generic.email') }}</label>
                    <div class="controls">
                        <input type="text" name="email" id="email" value="{{ old('email') }}" placeholder="{{ __('voyager::generic.email') }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-group form-group-default" id="passwordGroup">
                    <label>{{ __('voyager::generic.password') }}</label>
                    <div class="controls">
                        <input type="password" name="password" placeholder="{{ __('voyager::generic.password') }}" class="form-control" required>
                    </div>
                    <button class="btn btn-view-password" type="button"><img src="{{ url("public/assets/img/view.png") }}" alt="view" class="icon img-fluid"></button>
                </div>

                <div class="form-group" id="rememberMeGroup">
                    <div class="controls">
                        <label for="remember" class="remember-me-text">
                            <input type="checkbox" name="remember" id="remember" value="1">{{ __('voyager::generic.remember_me') }}<span class="checkmark"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block login-button">
                    <span class="signingin hidden"><span class="voyager-refresh"></span> {{ __('voyager::login.loggingin') }}...</span>
                    <span class="signin">{{ __('voyager::generic.login') }}</span>
                </button>

            </form>

            <div style="clear:both"></div>

            @if(!$errors->isEmpty())
            <div class="alert alert-red">
                <ul class="list-unstyled">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <br>


        </div> <!-- .login-container -->

    </div>


</div>
<div class="developedByText">
    <p style="color:#000;font-weight:bold;text-align:center">Design & Developed By <a href="https://designspartans.com/" target="_blank">Design Spartans</a></p>
</div>
@endsection

@section('post_js')

<script>
    var btn = document.querySelector('button[type="submit"]');
    var form = document.forms[0];
    var email = document.querySelector('[name="email"]');
    var password = document.querySelector('[name="password"]');
    btn.addEventListener('click', function(ev){
        if (form.checkValidity()) {
            btn.querySelector('.signingin').className = 'signingin';
            btn.querySelector('.signin').className = 'signin hidden';
        } else {
            ev.preventDefault();
        }
    });
    email.focus();
    document.getElementById('emailGroup').classList.add("focused");

    // Focus events for email and password fields
    email.addEventListener('focusin', function(e){
        document.getElementById('emailGroup').classList.add("focused");
    });
    email.addEventListener('focusout', function(e){
        document.getElementById('emailGroup').classList.remove("focused");
    });

    password.addEventListener('focusin', function(e){
        document.getElementById('passwordGroup').classList.add("focused");
    });
    password.addEventListener('focusout', function(e){
        document.getElementById('passwordGroup').classList.remove("focused");
    });

</script>
<script>
    document.querySelector('.btn-view-password').addEventListener('click', function() {
        var parent = this.parentElement;
        var input = parent.querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    });
</script>
@endsection
