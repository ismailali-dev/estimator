<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;
    protected $fillable = ["title", "message", "contractors", "fcm_response"];

    public function getNotificationDateAttribute($value){
        return (new Carbon($value))->format('Y-m-d\\TH:i:s.v');
    }
}
