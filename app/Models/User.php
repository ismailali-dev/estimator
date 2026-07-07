<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    //protected $appends = ["company","isExpired","subscriptionLeft"];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'name',
        'email',
        'password',
        'raw_password',
        'first_name',
        'last_name',
        'phone',
        'job_type',
        'sign',
        'company_id',
        'position',
        'is_admin',
        'status',
        'email_verified_at',
        'type'
    ];

     protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'name'
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $additional_attributes = ['full_name'];


//   protected static function booted()
// {
//     static::addGlobalScope('hide_tmp_emails', function (Builder $builder) {
//         $builder->where('email', 'not like', '%.tmp');
//     });
// }
    public function modulePermissions()
    {
        return $this->belongsToMany(ModulePermission::class, 'user_module_permissions', 'user_id', 'module_permission_id')
            ->withTimestamps();
    }

    public function scopeContractors($query)
    {
        return $query->where('is_admin', 1)
                     ->where(function ($q) {
                         $q->where('role_id', '!=', 1)
                           ->orWhereNull('role_id');
                     });
    }

    public function devices()
    {
        return $this->morphMany(\App\Models\Device::class, 'deviceable');
    }




     public function stripeAccount()
    {
        return $this->hasOne(StripeAccount::class);
    }

    public function getRawPasswordAttribute($value)
    {

        // Check if the value is null or an empty string
        if (is_null($value) || $value === '') {
            return null; // Or return a default value if needed
        }

        // Attempt to decode the value, handling any potential errors
        try {
            return base64_decode($value, true); // Using strict mode to avoid invalid characters
        } catch (\Exception $e) {
            return null;
        }
    }



    public function routeNotificationForFirebase()
    {
        return $this->device_token;
    }

    public function getFirstNameBrowseAttribute()
    {
        return ucfirst(strtolower($this->first_name)) .' '.ucfirst(strtolower($this->last_name));
    }

    public function getFullNameAttribute():string
    {
        return ucfirst(strtolower($this->first_name)) .' '.ucfirst(strtolower($this->last_name));
    }

    public function userAddress():HasOne
    {
        return $this->hasOne(UserAddress::class);
    }

    public function company():BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function suppliers():HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers():HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function codes():HasMany
    {
        return $this->hasMany(Code::class);
    }

    public function InvoiceSetting():HasOne
    {
        return $this->hasOne(UserInvoiceSetting::class);
    }

    public function GlobalSetting():HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function JobTypes():HasOne
    {
        return $this->hasOne('App\Models\JobTypes', 'id', 'job_type');
    }

    public function memberships():HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function subscriptions():HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function templates():HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function settings(){
        return $this->hasMany(Setting::class);
    }

    // public function hasSubscription($module = ""){
    //     $authorized = false;
    //     // template, invoice, home_depot_support
    //     if($module){
    //         $authorized = $this->subscriptions()
    //             ->where("is_active",1)
    //             ->where("is_cancelled",false)
    //             ->where("slug",$module)
    //             ->count() == 0 ? false : true;
    //     }

    //   return $authorized;
    // }

    // public function hasSubscription($module = "")
    // {
    //     $authorized = false;

    //     // Check if the user is an admin
    //     if ($this->is_admin) {
    //         if ($module) {

    //             $authorized = $this->subscriptions()
    //                 ->where("is_active", 1)
    //                 ->where(function ($query) {
    //                     // Check the conditions for the subscription
    //                     $query->where(function ($query) {
    //                         $query->where("is_cancelled", 0) // Not cancelled
    //                               ->where(function ($query) {
    //                                   $query->whereNull('ends_at') // No end date means subscription is valid
    //                                         ->orWhere('ends_at', '>=', now()); // If ends_at is in the future, it's valid
    //                               });
    //                     })
    //                     // Allow cancelled subscriptions that still have time remaining until the 'ends_at' date
    //                     ->orWhere(function ($query) {
    //                         $query->where('is_cancelled', 1)
    //                               ->where('ends_at', '>=', now()); // Cancelled, but still within the subscription period
    //                     });
    //                 })
    //                 ->where("slug", $module)
    //                 ->exists();

    //                 // dd($authorized);
    //         }
    //     } else {
    //         // If not an admin, check the parent user's subscription
    //         $parentUser = User::find($this->parent_id);
    //         if ($parentUser) {
    //             if ($module) {
    //                 $authorized = $parentUser->subscriptions()
    //                     ->where("is_active", 1)
    //                     ->where(function ($query) {
    //                         // Check the conditions for the subscription
    //                         $query->where(function ($query) {
    //                             $query->where("is_cancelled", 0); // Not cancelled
    //                                 //   ->where(function ($query) {
    //                                 //       $query->whereNull('ends_at') // No end date means subscription is valid
    //                                 //             ->orWhere('ends_at', '>=', now()); // If ends_at is in the future, it's valid
    //                                 //   });
    //                         })
    //                         // Allow cancelled subscriptions that still have time remaining until the 'ends_at' date
    //                         ->orWhere(function ($query) {
    //                             $query->where('is_cancelled', 1);
    //                                 //   ->where('ends_at', '>=', now()); // Cancelled, but still within the subscription period
    //                         });
    //                     })
    //                     ->where("slug", $module)
    //                     ->exists();
    //             }
    //         }
    //     }

    //     return $authorized;
    // }


    public function hasSubscription($module = "")
    {
        if (!$module) {
            return false;
        }


        $user = $this->is_admin ? $this : User::find($this->parent_id);

        if (!$user) {
            return false;
        }

        return $user->subscriptions()
            ->where('is_active', 1)
            ->where('slug', $module)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('is_cancelled', 0)
                          ->where(function ($q) {
                              $q->whereNull('ends_at')
                                ->orWhere('ends_at', '>=', now());
                          });
                })->orWhere(function ($query) {
                    $query->where('is_cancelled', 1)
                          ->where('ends_at', '>=', now());
                });
            })
            ->exists();
    }


    public function hasFullModuleAccess(): bool
    {

        if (!$this) {
            return false;
        }

        // Use the existing hasModuleAccess method for full access check
        return $this->hasModuleAccess(null); // null => checks full access (1-11)
    }

    public function hasModuleAccess($modules = null): bool
    {
        // If the user has no parent_id, grant access to all modules
        if (is_null($this->parent_id)) {
            return true;
        }

        // Default modules enabled for everyone
        $defaultEnabledIds = [4, 5, 6, 11];

        // User assigned modules
        $userModuleIds = UserModulePermission::where('user_id', $this->id)
            ->pluck('module_permission_id')
            ->toArray();

        // Combine assigned + default modules
        $allUserModules = array_unique(array_merge($userModuleIds, $defaultEnabledIds));

        if (is_null($modules)) {
            // Check for full access (1-11)
            $fullModuleIds = range(1, 11);
            return empty(array_diff($fullModuleIds, $allUserModules));
        }

        // If a single module ID is passed, convert to array
        $modules = (array) $modules;

        // Check if user has all specified module(s)
        return empty(array_diff($modules, $allUserModules));
    }




    function get_timezone_from_country_code($countryCode)
    {
        $map = [
            'PK' => 'Asia/Karachi',
            'IN' => 'Asia/Kolkata',
            'US' => 'America/New_York',
            'AE' => 'Asia/Dubai',
            'GB' => 'Europe/London',
            'CA' => 'America/Toronto',
            'AU' => 'Australia/Sydney',
            'DE' => 'Europe/Berlin',
            'FR' => 'Europe/Paris',
        ];

        return $map[$countryCode] ?? config('app.timezone', 'UTC');
    }


   public function hasHomeDepotSupport()
{
    // Get the most recent subscription with the specific slug
    $latestSubscription = $this->subscriptions()
        ->where("is_active", 1)
        ->where(function ($query) {
            // Active subscriptions or cancelled subscriptions still within their valid period
            $query->where("is_cancelled", false)
                ->orWhere(function ($subQuery) {
                    // Canceled subscriptions with 'ends_at' in the future or today
                    $subQuery->where("is_cancelled", true)
                        ->where("ends_at", ">", now());
                })
                ->orWhere(function ($subQuery) {
                    // Active subscriptions with 'ends_at' as null (still valid)
                    $subQuery->where("ends_at", null);
                });
        })
        ->where("slug", Membership::SUBSCRIPTION_HOME_DEPOT_SUPPORT)
        ->orderBy("created_at", "desc") // Order by the latest created subscription
        ->first(); // Get the latest subscription

    // Return true if a valid subscription exists
    return $latestSubscription ? true : false;
}


    public function toFlattenedArray()
    {
        $array = $this->toArray();
        $flattened = array_merge($array, [
            'city' => optional($this->userAddress)->city,
            'state_id' => optional($this->userAddress)->state_id,
            'zip_code' => optional($this->userAddress)->zip_code,
            'company_name' => $this->company->name ?? null,
            'company_address' => $this->company->address ?? null,
            'license_no' => $this->company->license_no ?? null,
            'sign' => $this->signUrl()
        ]);

        // Remove nested arrays and IDs from relations
        unset(
            $flattened['company'],
            $flattened['user_address']
        );

        return $flattened;
    }

 private function signUrl()
{
    if (empty($this->sign)) {
        return null;
    }

    // Already a full URL (e.g. external or data URL)
    if (filter_var($this->sign, FILTER_VALIDATE_URL)) {
        return $this->sign;
    }

    // Normalize any legacy prefix down to the relative path under storage/app/public
    $file = str_ireplace(['storage/app/public/', 'storage/app/', 'storage/'], '', $this->sign);

    if (Storage::disk('public')->exists($file)) {
        return url('storage/' . $file);
    }

    return $this->sign;
}

       protected static function booted()
    {
        static::creating(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name;
        });
    }


}

