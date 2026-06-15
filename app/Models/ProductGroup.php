<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductGroup extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'group_name', 'status', 'user_id', 'company_id', 'group_order',
    ];

    public function codes():HasMany
    {
        return $this->hasMany(Code::class);
    }

}
