<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        return redirect()->route('dashboard');
    }

    protected function _checkUser($value){
        $query = User::where('email',$value)->where("status",1)->where("type", "!=", 3)->first();
        return (!empty($query));
    }

    protected function validateLogin(Request $request)
    {
        Validator::extend('user_validator', function ($attribute, $value) {
            return $this->_checkUser($value);
        });


        $request->validate([
            $this->username() => [
                'required',
                'email',
                'user_validator'
            ],
        ],[
            'user_validator' => 'Your account is no longer have access'
        ]);
    }
}
