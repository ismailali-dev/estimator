<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEstimatedAnnualJobCost extends Model
{
    use HasFactory;

    protected $table = 'user_estimated_annual_job_costs'; // Specify the table name

    // Specify the columns that are fillable
    protected $fillable = [
        'company_id',
        'setting_type_id',
        'estimated_annual_job_cost'
    ];

    // Relationship with the Company model
    public function company()
    {
        return $this->belongsTo(Company::class); // Assuming the 'company_id' foreign key
    }

    // Relationship with the SettingType model
    public function settingType()
    {
        return $this->belongsTo(SettingType::class); // Assuming the 'setting_type_id' foreign key
    }

    // Relationship with the User model via Company
    public function user()
    {
        return $this->hasOneThrough(User::class, Company::class, 'id', 'company_id');
    }
}