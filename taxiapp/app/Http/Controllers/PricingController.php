<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pricing;

class PricingController extends Controller
{
    // Obtener precios
    public function index()
    {
        $pricing = Pricing::first();
        return response()->json($pricing);
    }

    // Actualizar precios
    public function update(Request $request)
    {
        $pricing = Pricing::first();
        if (!$pricing) {
            $pricing = new Pricing();
        }

        $request->validate([
            'price_base' => 'required|numeric|min:0',
            'price_per_minute' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
        ]);

        $pricing->update([
            'price_base' => $request->price_base,
            'price_per_minute' => $request->price_per_minute,
            'price_per_km' => $request->price_per_km,
        ]);

        return response()->json(['message' => 'Pricing updated successfully']);
    }
}
