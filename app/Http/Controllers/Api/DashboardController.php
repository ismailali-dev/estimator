<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiBaseController
{
    //

    public function index():JsonResponse
    {
        return  $this->sendJsonResponse();
    }
}
