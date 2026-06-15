<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;


class Subscription extends Model
{
    
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'ends_at' => 'datetime',
        'renewable_date' => 'date',
        'cancelled_at' => 'datetime', // timestamp bhi Laravel mein datetime cast se handle ho jata hai
    ];
    
   
    public $additional_attributes = ['plan_type'];
    
    
    public function getPlanTypeAttribute()
    {
        
        dd(1);
        if (str_contains($this->slug, 'mon_')) {
            return 'Monthly';
        } elseif (str_contains($this->slug, 'year_')) {
            return 'Yearly';
        }
        return 'N/A';
    }
    
    

    // protected static function booted()
    // {
    //     static::creating(function ($subscription) {
    //         // Get membership details from the selected membership_id
    //         $membership = Membership::find($subscription->membership_id);
    
    //         if ($membership) {
    //             $subscription->title = $membership->title;
    //             $subscription->amount = $membership->amount;
    //             $subscription->slug = $membership->slug;
    //         }
            
    //         $user = User::with('company')->find($subscription->user_id);
    //         if ($user && $user->company) {
    //             $subscription->company_id = $user->company->id;
    //         }
            

    //         $subscription->status = $subscription->is_active == 1 ? 'active' : 'pending';
            
    //         // Platform default
    //         $subscription->platform = 'google';
    
    //         // Set dates based on renewable_type
    //         $now = Carbon::now();
            
    //         if ($subscription->renewable_type === 'month') {
    //             $subscription->renewable_date = $now->copy()->addMonth();
    //             $subscription->ends_at = $now->copy()->addMonth();
    //         } elseif ($subscription->renewable_type === 'year') {
    //             $subscription->renewable_date = $now->copy()->addYear();
    //             $subscription->ends_at = $now->copy()->addYear();
    //         }
                
    //         // if ($subscription->renewable_type === 'month') {
    //         //     $subscription->renewable_date = $now->copy()->addMonth();
    //         //     $subscription->ends_at = $now->copy()->addMonth();
    //         // } elseif ($subscription->renewable_type === 'year') {
    //         //     $subscription->renewable_date = $now->copy()->addYear();
    //         //     $subscription->ends_at = $now->copy()->addYear();
    //         // } else {
    //         //     // Fallback if none set
    //         //     $subscription->renewable_date = $now;
    //         //     $subscription->ends_at = $now;
    //         // }
    
    //         // Optional: if you want to auto-activate
    //         if ($subscription->is_active === null) {
    //             $subscription->is_active = 1;
    //         }
    
    //         // You can log or debug to confirm
    //         // \Log::info('Creating Subscription (final):', $subscription->toArray());
    //     });
    // }
    
    protected static function booted()
{
    static::creating(function ($subscription) {

        // Membership auto-fill
        $membership = Membership::find($subscription->membership_id);

        if ($membership) {
            $subscription->title = $membership->title;
            $subscription->amount = $membership->amount;
            $subscription->slug = $membership->slug;

            // Agar renewable_type pass nahi hua to membership se le lo
            if (!$subscription->renewable_type) {
                $subscription->renewable_type = strtolower($membership->renewable_type);
            }
        }

        // User company assign
        $user = User::with('company')->find($subscription->user_id);
        if ($user && $user->company) {
            $subscription->company_id = $user->company->id;
        }

        // Default active
        if ($subscription->is_active === null) {
            $subscription->is_active = 1;
        }

        // Status
        $subscription->status = $subscription->is_active == 1 ? 'active' : 'pending';

        $now = Carbon::now();

        /**
         * 🔥 ADMIN VS AUTO LOGIC
         */
        if ($subscription->is_admin_allowed) {

            // ✅ ADMIN CASE
            $subscription->platform = 'admin';

            // Agar admin ne ends_at manually nahi diya
            if (!$subscription->ends_at) {
                if ($subscription->renewable_type === 'month') {
                    $subscription->renewable_date = $now->copy()->addMonth();
                    $subscription->ends_at = $now->copy()->addMonth();
                } elseif ($subscription->renewable_type === 'year') {
                    $subscription->renewable_date = $now->copy()->addYear();
                    $subscription->ends_at = $now->copy()->addYear();
                }
            }

        } else {

            // ✅ AUTO (Google / Apple)
            if (!$subscription->platform) {
                $subscription->platform = 'google'; // default
            }

            // Dates only set if not provided (mobile se aa sakti hain)
            if (!$subscription->ends_at) {
                if ($subscription->renewable_type === 'month') {
                    $subscription->renewable_date = $now->copy()->addMonth();
                    $subscription->ends_at = $now->copy()->addMonth();
                } elseif ($subscription->renewable_type === 'year') {
                    $subscription->renewable_date = $now->copy()->addYear();
                    $subscription->ends_at = $now->copy()->addYear();
                }
            }
        }

    });
}

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }
    
     public function getAmountAttribute($amount)
    {
        
        // Check if amount is not null, then format it with commas
        return $amount !== null ? number_format($amount,2) : null;
    }
    
    
    
    
     public function getPlatformAttribute($Platform)
    {
        
        // Check if amount is not null, then format it with commas
        return $Platform !== null ? ucfirst($Platform) : null;
    }
    
    
    
}
