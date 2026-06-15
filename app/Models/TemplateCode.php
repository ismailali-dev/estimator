<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TemplateCode extends Model
{
    use HasFactory;

    protected $fillable = [
        "code_id",
        "template_id",
        "user_id"
    ];

    public function Template():BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function SingleCode():HasOne
    {
        return $this->hasOne(Code::class, "id", "code_id");
    }

    
}
