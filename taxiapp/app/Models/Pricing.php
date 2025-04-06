<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $table = 'pricings';

    protected $fillable = [
        'price_base',
        'price_per_minute',
        'price_per_km',
    ];
}
