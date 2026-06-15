<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\View;

trait SubModuleTrait
{
    public function __construct()
    {
        $cls = explode("\\",get_called_class());
        View::share('controllerName', $cls[count($cls)-1]);
    }
}
