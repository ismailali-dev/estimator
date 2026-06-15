<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitBudgetEstimateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'user_id',
        'company_id',
        'title',
        'slug',
        'actual_cost',
        'difference',
        'type',
        'sort_order',
    ];

    // Relationships (optional)
    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}