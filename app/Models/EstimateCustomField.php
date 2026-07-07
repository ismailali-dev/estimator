<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'custom_string',
        'custom_integer',
    ];

    protected $casts = [
        'custom_integer' => 'integer',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }
}