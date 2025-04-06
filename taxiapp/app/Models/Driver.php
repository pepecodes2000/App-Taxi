<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cellphone',
        'user_id',
        'status',
        'license_plate',
        'brand',
        'year',
        'driver_license',
        'latitude',
        'longitude',
    ];
    

    // Relación con usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Un conductor puede recibir múltiples solicitudes de viaje
    public function rideRequests()
    {
        return $this->hasMany(RideRequest::class);
    }
}
