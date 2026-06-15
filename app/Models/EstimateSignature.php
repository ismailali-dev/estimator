<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateSignature extends Model
{
    use HasFactory;

    // Define the table name if it's not the default plural form
    protected $table = 'estimate_signatures';

    // Define the fields that are mass assignable
    protected $fillable = [
        'user_id',
        'estimate_id',
        'estimator_signature',
        'estimator_signed_at',
        'customer_signature',
        'customer_signed_at',
    ];

    // Casting the date fields to Carbon instances
    protected $casts = [
        'estimator_signed_at' => 'datetime',
        'customer_signed_at' => 'datetime',
    ];

    // Relationship to the user who signed the estimate
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to the estimate
    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }
}
