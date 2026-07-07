<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;


class Estimate extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    protected $hidden = ["estimate_date_time"];

    public const ESTIMATE_TYPE = [
        "estimate_order" => "Estimate Order",
        "extra_work_order" => "Extra Work Order",
        "credit_work_order" => "Credit Work Order",
    ];

    public function getTotalAttribute(){
        return $this->contract_price + $this->extra_work_orders;
    }

    public function getEstimateDateTimeAttribute($value){
        return (new Carbon($value))->format('Y-m-d\\TH:i:s.v');
    }

    public function getEstimateDateAttribute($value){
        return (new Carbon($value))->format("m/d/Y");
    }

    public function getPbNumberAttribute(){
        return str_pad($this->id, 4, "0", STR_PAD_LEFT);
    }

    public function estimateType():BelongsTo
    {
        return $this->belongsTo(EstimateType::class,"type");
    }

    public function customer():BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sheet():HasMany
    {
        return $this->hasMany(EstimateSheet::class)->with('code');
    }

    public function company():BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }


    public function customFields():HasMany
    {
        return $this->hasMany(EstimateCustomField::class);
    }
}
