<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Html\Editor\Fields\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Code extends Model
{
    use HasFactory,SoftDeletes;
   
    protected $fillable = [
        "code_name",
        "code_date",
        "product_group_id",
        "sort_order",
        "supplier_id",
        "product_name",
        "sku_no",
        "model_no",
        "unit_of_measure",
        "labor_cost",
        "material_cost",
        "misc_cost",
        "description",
        "image",
        'type',
        "operator",       // new field
        "adjust_num",     // new field
        "adjusted_cost",  // new field
    ];
    

    public function getFullImageAttribute(){
        $file = '';
        if (!empty($this->image)){
            $file = str_ireplace("storage/app/", "", $this->image);
            if (Storage::exists($file)){
                $file = asset("storage/app/".$file);
                return $file;
            } else {
                if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                    return $this->image;
                }
            }
        }
        return $file;
    }
    
    
    public function newQuery($builder = null)
    {
        return parent::newQuery($builder)->withTrashed();
    }
    
    public function getMaterialCostAttribute($value)
    {
        return $value ?? 0; // Return 0 if the value is null
    }

    public function ProductGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}
