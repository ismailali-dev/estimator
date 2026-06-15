<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingType extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    public const SETTING_TYPE_FOR_ESTIMATE = 1;
    public const SETTING_TYPE_FOR_PROFIT_BUDGET = 2;
}
