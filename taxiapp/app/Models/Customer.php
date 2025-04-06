<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RideRequest;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'phone'
    ];

    // Un cliente puede solicitar múltiples viajes
    public function rideRequests()
    {
        return $this->hasMany(RideRequest::class, 'customer_id');
    }

}

