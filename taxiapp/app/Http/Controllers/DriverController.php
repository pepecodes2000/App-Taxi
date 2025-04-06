<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    // Crear un nuevo conductor
    public function store(Request $request)
    {
        // Validar datos
        $request->validate([
            
            'user_id' => 'required|exists:users,id|unique:drivers,user_id',
            'license_plate' => 'required|string|unique:drivers,license_plate',
            'brand' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'driver_license' => 'required|string|unique:drivers,driver_license',
        ]);

        // Crear conductor
        $driver = Driver::create($request->all());

        return response()->json(['message' => 'Conductor registrado correctamente', 'driver' => $driver], 201);
    }

    // Actualizar conductor
    public function update(Request $request, $id)
    {
        // Buscar conductor
        $driver = Driver::find($id);
        if (!$driver) {
            return response()->json(['message' => 'Conductor no encontrado'], 404);
        }

        // Validar datos
        $request->validate([
            'name' => 'nullable|string',
            'cellphone' => 'nullable|string',
            'license_plate' => 'sometimes|string|unique:drivers,license_plate,' . $id,
            'brand' => 'sometimes|string|max:100',
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'driver_license' => 'sometimes|string|unique:drivers,driver_license,' . $id,
            'status' => 'sometimes|in:available,busy,inactive',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Actualizar datos
        $driver->update($request->all());

        return response()->json(['message' => 'Conductor actualizado correctamente', 'driver' => $driver]);
    }

    // Obtener todos los conductores
    public function index()
    {
        return response()->json(Driver::with('user')->get());
    }

    // Obtener un conductor por ID
    public function show($id)
    {
        $driver = Driver::with('user')->find($id);
        if (!$driver) {
            return response()->json(['message' => 'Conductor no encontrado'], 404);
        }

        return response()->json($driver);
    }

    // obtener conductor autentificado
    public function getAuthenticatedDriver()
    {
        $user = Auth::user(); // Obtener el usuario autenticado

        if (!$user) {
            return response()->json(["error" => "Usuario no autenticado"], 401);
        }

        if ($user->role !== 'driver') {
            return response()->json(["error" => "No autorizado"], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if ($driver) {
            return response()->json($driver);
        } else {
            return response()->json(["error" => "Conductor no encontrado"], 404);
        }
    }


    // Eliminar un conductor
    public function destroy($id)
    {
        $driver = Driver::find($id);
        if (!$driver) {
            return response()->json(['message' => 'Conductor no encontrado'], 404);
        }

        $driver->delete();

        return response()->json(['message' => 'Conductor eliminado correctamente']);
    }
}
