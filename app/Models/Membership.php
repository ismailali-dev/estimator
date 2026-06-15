<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yajra\DataTables\Html\Editor\Fields\BelongsTo;

class Membership extends Model
{
    use HasFactory;

    const SUBSCRIPTION_TEMPLATE = 'template';
    const SUBSCRIPTION_INVOICE = 'invoice';
    const SUBSCRIPTION_MULTI_USER_ACCESS = 'multi_user_access';
    const SUBSCRIPTION_HOME_DEPOT_SUPPORT = 'home_depot_support';
    const SUBSCRIPTION_CODE_BOOK_SUPPORT = "code_book_support";
    const SUBSCRIPTION_GLOBAL_SETTING_SUPPORT = "global_setting_support";
    const SUBSCRIPTION_CREDIT_CARD_SUPPORT = "credit_card_support";

    

    protected $guarded = ['id'];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions():HasMany
    {
        return $this->hasMany(Subscription::class);
    }
    
    public function getRenewableTypeForProduct($store, $productId)
    {
        if ($store === 'play_store') {
            if ($this->play_store_monthly_product_id === $productId) {
                return 'month';
            } elseif ($this->play_store_yearly_product_id === $productId) {
                return 'year';
            }
        } elseif ($store === 'app_store') {
            if ($this->app_store_monthly_product_id === $productId) {
                return 'month';
            } elseif ($this->app_store_yearly_product_id === $productId) {
                return 'year';
            }
        }
    
        return null; // fallback
    }


}
