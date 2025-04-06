<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Driver;

class AuthController extends Controller
{
    // Método de registro
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cellphone' => 'required|string|unique:users,cellphone',
            'role' => 'required|in:admin,driver',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'cellphone' => $request->cellphone,
            'role' => $request->role,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        // Redirigir según el rol del usuario
        if ($user->role === 'admin') {
            return redirect()->route('admin'); 
        } elseif ($user->role === 'driver') {
            return redirect()->route('conductor'); 
        }

        return redirect()->route('home'); // Ruta por defecto si no se cumple ninguna condición
    }


    // Método de inicio de sesión
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        $user = Auth::user();
        
        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user
        ]);
    }


    public function getUser(Request $request)
    {
        $user = auth()->user();

        // Si el usuario es un conductor, buscar en la tabla 'drivers'
        if ($user->role === 'driver') {
            $driver = Driver::where('user_id', $user->id)->first();

            // Adjuntar los datos del conductor al usuario
            $user->driver = $driver;
        }

        return response()->json($user);
    }



    // Obtener todos los usuatrios
    public function listUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Obtener un Usuario Especifico
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user);
    }

    // Actualizar usuario
    public function updateUser(Request $request, $id)
    {
        // Buscar usuario
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validar datos
        $request->validate([
            'name' => 'required|string|max:255',
            'cellphone' => 'required|string|unique:users,cellphone,' . $id,
            'role' => 'required|in:admin,driver',
            'email' => 'required|string|email|max:150|unique:users,email,' . $id,
        ]);

        // Actualizar datos
        $user->update([
            'name' => $request->name,
            'cellphone' => $request->cellphone,
            'role' => $request->role,
            'email' => $request->email,
        ]);
        
        return response()->json(['message' => 'Usuario actualizado correctamente', 'user' => $user]);
    }

    // Eliminar un Usuario
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
    // Método de cierre de sesión
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

}

