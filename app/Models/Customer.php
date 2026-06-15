<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Editor\Fields\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory,SoftDeletes;

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function getNameAttribute(){
        return $this->first_name . " " . $this->last_name;
    }

    public function getCustomerStateAttribute():string
    {
        $s = State::where("id", "=", $this->state)->first(["state_name"]);
        $value = "";
        if (!empty($s)){
            $value = $s->state_name;
        }
        return $value;
    }

    public function getCustomerCityAttribute():string
    {
        $c = City::where("id", "=", $this->city)->where("state_id", "=", $this->state)->first(["city_name"]);
        $value = "";
        if (!empty($c)){
            $value = $c->city_name;
        }
        return $value;
    }
}
