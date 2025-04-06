<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RideRequest;

class RideRequestController extends Controller
{
    // Obtener todas las solicitudes de viaje
    public function index()
    {
        return response()->json(RideRequest::all());
    }

    // Obtener una solicitud específica
    public function show($id)
    {
        $request = RideRequest::find($id);
        if (!$request) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }
        return response()->json($request);
    }

    // Crear una nueva solicitud de viaje
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'status' => 'required|string',
            'origin_location' => 'required|string',
            'destination_location' => 'required|string',
            'distance_km' => 'required|numeric|min:0',
            'service_time_min' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0'
        ]);

        $RideRequest = RideRequest::create($validatedData);
        return response()->json($RideRequest, 201);
    }

    // Actualizar una solicitud de viaje existente
    public function update(Request $request, $id)
    {
        $RideRequest = RideRequest::find($id);
        if (!$RideRequest) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        $validatedData = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'driver_id' => 'sometimes|nullable|exists:drivers,id',
            'status' => 'sometimes|string',
            'origin_location' => 'sometimes|string',
            'destination_location' => 'sometimes|string',
            'distance_km' => 'sometimes|numeric|min:0',
            'service_time_min' => 'sometimes|numeric|min:0',
            'total_price' => 'sometimes|numeric|min:0'
        ]);

        $RideRequest->update($validatedData);
        return response()->json($RideRequest);
    }

    // Eliminar una solicitud de viaje
    public function destroy($id)
    {
        $RideRequest = RideRequest::find($id);
        if (!$RideRequest) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        $RideRequest->delete();
        return response()->json(['message' => 'Solicitud eliminada correctamente']);
    }
}
