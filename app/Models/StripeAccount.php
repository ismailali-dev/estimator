<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'stripe_account_id', 
        'status', 
        'stripe_data'
    ];

    protected $casts = [
        'stripe_data' => 'array',  // To cast the stripe_data JSON into an array
    ];

    // Define the relationship to the User (or Contractor) model
    public function user()
    {
        return $this->belongsTo(User::class);  // Adjust the model if you're using a different one
    }
}