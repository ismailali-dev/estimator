<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yajra\DataTables\Html\Editor\Fields\BelongsTo;

class State extends Model
{
    use HasFactory;

    protected $fillable = ["city_name"];

    public function cities():HasMany
    {
        return $this->hasMany(City::class);
    }

    public static function stateCities(): array
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
        return $stateWithCities;
    }

    public function SupplierState():BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
