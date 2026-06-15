<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractorTransaction extends Model
{
    use HasFactory;

    protected $table = 'contractor_transactions';
    
    
    protected $fillable = [
        'contractor_id',
        'estimate_id',
        'customer_id',
        'amount',
        'description',
        'tax',
        'grand_total',
        'status',
        'stripe_transaction_id'
    ];

    // Relationships
    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
