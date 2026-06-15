<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserInvoiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "color", "logo"
    ];

    public function User():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullLogoAttribute()
    {
        $file = "";
        if (!empty($this->logo)) {
            $file = str_ireplace("storage/app/", "", $this->logo);
            if (Storage::exists($file)) {
                // Generate the URL without 'public'
                $file = url("storage/app/" . $file);
                return $file;
            } else {
                $file = "";
            }
        }
        return $file;
    }
}

