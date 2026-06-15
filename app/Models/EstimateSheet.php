<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateSheet extends Model
{
    use HasFactory;

    protected  $guarded = ["id"];

    protected $casts = [
    'material_cost' => 'float',
    'labor_cost' => 'float',
    'material_adjust_cost' => 'float',
    'misc_cost' => 'float',
    'quantity' => 'integer',
];
    
   

   public function getMaterialCostAttribute($value)
{
    return (float) ($value ?? 0);
}

public function getLaborCostAttribute($value)
{
    return (float) ($value ?? 0);
}
    // public function getTotalActualMaterialCostAttribute()
    // {
    //     $code = $this->code;
    
    //     if (empty($code->adjust_num) || $code->adjust_num == 0 || $code->adjust_num == '1.00') {
    //         return ((int) $this->quantity * $this->actual_material_cost) + $this->misc_cost;
    //     } else {
    //         return ((int) $this->quantity * $code->adjusted_cost) + $this->misc_cost;
    //     }
    // }
    public function getTotalLaborCostAttribute(){
        
        $code = $this->code;
        return $code->labor_cost * $this->quantity;
    }

    public function getTotalMaterialCostAttribute()
    {
        $code = $this->code;
    
        // Default total
        $totalCost = 0;
    
        // Validate code aur quantity
        if (!$code || empty($this->quantity) || $this->quantity == 0) {
            return 0;
        }
    
        // Determine cost type
        $costPerUnit = (empty($code->adjust_num) || $code->adjust_num == 0 || $code->adjust_num == '1.00')
            ? (float) $code->material_cost
            : (float) $code->adjusted_cost;
    
        // Final total
        $totalCost = ((float) $this->quantity * $costPerUnit) + (float) $this->misc_cost;
    
        return round($totalCost, 2);
    }



    public function getTotalMaterialCostWithoutMiscAttribute(){
        return $this->material_cost * $this->quantity;
    }
    
    
    // public function Code():BelongsTo
    // {
    //     return $this->belongsTo(Code::class);
    // }
    
    // public function supplier():BelongsTo
    // {
    //     return $this->belongsTo(Supplier::class);
    // }

    
    
    public function code()
    {
        return $this->belongsTo(Code::class)->withTrashed()->orderBy('sort_order');
    }

    public function ProductGroup():BelongsTo
    {
        return $this->belongsTo(ProductGroup::class)->orderBy('group_order');
    }

    public function scopeProductGroups($query){
        return $query->group_by("product_group_id");
    }

    // Override the toArray method to include accessor values
    public function toArray()
    {
        $attributes = parent::toArray();

        // Include the accessor value in the array
        $attributes['total_material_cost'] = $this->total_material_cost;
        $attributes['total_material_cost_without_misc'] = $this->total_material_cost_without_misc;
        $attributes['total_labor_cost'] = $this->total_labor_cost;

        return $attributes;
    }
    
    public function suppliers()
    {
        return $this->hasMany(EstimateSheetSupplier::class);
    }
    
    public function laborSupplier()
    {
        return $this->hasOne(EstimateSheetSupplier::class)->where('type', 'Labor');
    }
    
    public function materialSupplier()
    {
        return $this->hasOne(EstimateSheetSupplier::class)->where('type', 'Material');
    }
    
    public function supplierByType($type)
    {
        return $this->suppliers()->where('type', $type)->first();
    }
    
//     public function getActualUserInputLaborCostAttribute() 
// {
//     return !is_null($this->attributes['actual_user_input_labor_cost'])
//         ? $this->attributes['actual_user_input_labor_cost']
//         : $this->attributes['actual_labor_cost'];
// }

// public function getActualUserInputMaterialCostAttribute() 
// {
//     return !is_null($this->attributes['actual_user_input_material_cost'])
//         ? $this->attributes['actual_user_input_material_cost']
//         : $this->attributes['actual_material_cost'];
// }
}
