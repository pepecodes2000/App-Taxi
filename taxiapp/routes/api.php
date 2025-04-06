<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\PricingController;
use App\Models\User;
use App\Models\Driver;
use App\Models\RideRequest;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']); // Registrar un nuevo usuario
Route::get('/users', [AuthController::class, 'listUsers']); // Obtener todos los usuarios
Route::get('/users/{id}', [AuthController::class, 'show']); // Obtener un Usuario
Route::put('/users/{id}', [AuthController::class, 'updateUser']);// Actualizar usuarios
Route::delete('/users/{id}', [AuthController::class, 'destroy']);// Eliminar Usuario

Route::post('/drivers', [DriverController::class, 'store']); // Crear conductor
Route::get('/drivers', [DriverController::class, 'index']); // Listar conductores
Route::get('/drivers/{id}', [DriverController::class, 'show']); // Obtener un conductor
Route::put('/drivers/{id}', [DriverController::class, 'update']); // Actualizar conductor
Route::delete('/drivers/{id}', [DriverController::class, 'destroy']); // Eliminar conductor
Route::get('/driver', [DriverController::class, 'getAuthenticatedDriver']);


Route::post('/customers', [CustomerController::class, 'store']); // Crear clientes
Route::get('/customers', [CustomerController::class, 'index']); // Listar clientes
Route::get('/customers/{id}', [CustomerController::class, 'show']); // Obtener un cliente
Route::put('/customers/{id}', [CustomerController::class, 'update']); // Actualizar cliente
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']); // Eliminar cliente

Route::get('ride_requests', [RideRequestController::class, 'index']); // Listar solicitudes
Route::post('ride_requests', [RideRequestController::class, 'store']); // Crear solicitud
Route::get('ride_requests/{id}', [RideRequestController::class, 'show']); // Obtener una solicitud específica
Route::put('ride_requests/{id}', [RideRequestController::class, 'update']); // Actualizar solicitud
Route::delete('ride_requests/{id}', [RideRequestController::class, 'destroy']); // Eliminar solicitud

Route::get('/webhook', [WhatsAppController::class, 'token']); // Validar webhook con Meta
Route::post('/webhook', [WhatsAppController::class, 'listen']); // Recibir mensajes de WhatsApp

Route::get('/pricing', [PricingController::class, 'index']);// Obtener el precio
Route::put('/pricing', [PricingController::class, 'update']);// Actualizar el precio

Route::get('/stats', function () {
    return response()->json([
        'totalUsers' => User::count(),
        'totalDrivers' => Driver::count(),
        'totalRequests' => RideRequest::count(),
        'totalIncome' => 0 // Por ahora en 0
    ]);
});

// Obtener los datos del usuario autenticado
Route::get('/me', [AuthController::class, 'getUser']);

// Iniciar sesión
Route::post('/login', [AuthController::class, 'login']) -> name('login');




