<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    
    protected $table = 'app_settings';

    protected $guarded = ["id"];

    public const SLUGS = [
            "material_markup" => "Material Markup",
            "labor_markup" => "Labor Markup",
            "tax" => "Tax"
    ];
    
     public function getTitleAttribute($value)
    {
        return ucfirst($value); // Capitalize the first letter
    }

}
