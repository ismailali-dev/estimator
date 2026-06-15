<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function users():HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees():HasMany
    {
        return $this->hasMany(User::class)->where("is_admin",0);
    }

    public function codes():HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function estimates(){
        return $this->hasMany(Estimate::class);
    }
    
    public function userEstimatedAnnualJobCosts()
    {
        return $this->hasMany(UserEstimatedAnnualJobCost::class);
    }
    
    


}
