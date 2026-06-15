<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityStateController extends ResponseController
{

    public function index():JsonResponse
    {
        $States = State::where("status", "=", 1)->orderBy("state_name")->get();
        $stateWithCities = [];
        foreach ($States as $state) {
            $cities = City::where("state_id", "=", $state->id)->where("status", "=", 1)->orderBy("city_name")->get(["id", "city_name"]);
            if (!empty($cities)){
                $stateWithCities[] = [
                    "id" => $state->id,
                    "state_name" => $state->state_name,
                    "cities" => $cities
                ];
            }

        }
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["state_city"=>$stateWithCities];
        return  $this->sendJsonResponse();
    }
}
