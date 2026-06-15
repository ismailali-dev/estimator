<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateUserSync extends Model
{
    use HasFactory;

    protected $table = 'template_user_syncs';

    protected $fillable = [
        'user_id',
        'template_id',
        'status',
        'synced_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
