<?php


namespace App\Http\Controllers;


abstract class AuthenticatedController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

}
