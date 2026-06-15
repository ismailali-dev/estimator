<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingDocument extends Model
{
    use HasFactory;
    use softDeletes;

    protected $guarded = [];

    protected $casts = [
        'fields' => 'array',
        'signature_required' => 'boolean',
        'signed_at' => 'datetime',
    ];

    // ✅ relation with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // optional
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
