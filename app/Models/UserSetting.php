<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "tax", "labor_cost", "material_cost"
    ];

    public function User():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
