<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModulePermission extends Model
{
    protected $table = 'user_module_permissions';

    protected $fillable = [
        'user_id',
        'module_permission_id',
    ];

    public $timestamps = false;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(ModulePermission::class, 'module_permission_id');
    }
}