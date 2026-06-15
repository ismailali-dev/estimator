<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateType extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getInitialsAttribute(){
        return collect(explode(' ', $this->name))->map(function ($word) {
            return strtoupper(str_split($word)[0]);
        })->implode('');
    }

}
