<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CityStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $json = Storage::disk("local")->get("/json/US_States_and_Cities.json");
        $jsonCityWithStates = json_decode($json);
        if (!empty($jsonCityWithStates)){
            foreach ($jsonCityWithStates as $state=>$cities){
                $isStateExists = State::where("state_name", "=", $state)->first();
                if (empty($isStateExists)){
                    $newState = new State();
                    $newState->state_name = $state;
                    $newState->save();
                    $stateId = $newState->id;
                }else{
                    $state = $isStateExists->id;
                }
                foreach ($cities as $city){
                    $isStateCityExists = City::where("state_id", "=", $stateId)->where("city_name", "=", $city)->first();
                    if (empty($isStateCityExists)){
                        $newCity = new City();
                        $newCity->city_name = $city;
                        $newCity->state_id = $stateId;
                        $newCity->save();
                    }
                }
            }
        }
    }
}
