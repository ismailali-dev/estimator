<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use App\Models\EstimateType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateTypeController extends ResponseController
{
    public function index():JsonResponse
    {
        $estimateTypes = EstimateType::all();
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Estimate types fetched successfully.";
        $this->response_data["data"] = $estimateTypes->toArray();
        return  $this->sendJsonResponse();
    }
}
