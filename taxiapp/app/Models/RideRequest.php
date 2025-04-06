<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Customer;
use App\Models\Driver;

class RideRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 
        'driver_id', 
        'status',
        'origin_location', 
        'destination_location',
        'distance_km', 
        'service_time_min', 
        'total_price'
    ];

    protected $casts = [
        'origin_location' => 'array',
        'destination_location' => 'array'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
