<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTypeController extends ResponseController
{
    //

    public function index():JsonResponse
    {

        $listOfJobTypes = JobTypes::where("status", "=", 1)->get(["id", "title"]);
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["job_types"=>$listOfJobTypes];
        return  $this->sendJsonResponse();
    }
}
