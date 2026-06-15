<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Yajra\DataTables\Html\Editor\Fields\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory,SoftDeletes;


    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function code():BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function Codes():HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function getSupplierStateAttribute():string
    {
        $s = State::where("id", "=", $this->state)->first(["state_name"]);
        $value = "";
        if (!empty($s)){
            $value = $s->state_name;
        }
        return $value;
    }

    public function getSupplierCityAttribute():string
    {
        $c = City::where("id", "=", $this->city)->where("state_id", "=", $this->state)->first(["city_name"]);
        $value = "";
        if (!empty($c)){
            $value = $c->city_name;
        }
        return $value;
    }

}
