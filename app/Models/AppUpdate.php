<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUpdate extends Model
{
    protected $fillable = [
        'update_type', 'version', 'force_update',
    ];

    protected $casts = [
        'force_update' => 'boolean',
    ];
}