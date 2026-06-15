<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        "template_name"
    ];

    public function TemplateCodes():HasMany
    {
        return $this->hasMany(TemplateCode::class, "template_id", "id")->with("SingleCode", function($query){ $query->where("status", 1);});
    }

 /*   public function Codes():BelongsTo
    {
        return $this->belongsTo(Code::class);
    }*/

   public function codes(): HasManyThrough
   {
       return $this->hasManyThrough(Code::class,TemplateCode::class,'template_id','id','id','code_id');
   }


    public function TemplateCode():HasMany
    {
        return $this->hasMany(Code::class);
    }


}
