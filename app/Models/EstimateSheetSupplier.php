<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimateSheetSupplier extends Model
{
    use HasFactory,SoftDeletes;
     protected $fillable = [
        'estimate_sheet_id',
        'type',
        'supplier_id',
         'actual_cost',     // New field
    ];
    
     public function estimateSheet()
    {
        return $this->belongsTo(EstimateSheet::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    
}
