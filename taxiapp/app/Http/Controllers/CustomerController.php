<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Muestra la lista de clientes.
     */
    public function index()
    {
        $customers = Customer::all();
        return response()->json($customers);
    }

    /**
     * Almacena un nuevo cliente en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Cliente creado correctamente',
            'customer' => $customer
        ], 201);
    }

    /**
     * Muestra un cliente específico.
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        return response()->json($customer);
    }

    /**
     * Actualiza un cliente existente.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $id,
        ]);

        $customer->update($validated);

        return response()->json(['message' => 'Cliente actualizado correctamente']);
    }

    /**
     * Elimina un cliente.
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $customer->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }
}
