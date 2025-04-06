<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Session\Middleware\StartSession;

// Inicio
Route::middleware([StartSession::class])->group(function () {
    Route::get('/', function () {
        return view('home');
    })->name('home'); 
});

// Cerrar sesión
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Panel de administrador
Route::get('/admin', function () {
    return view('admin');
})->middleware(['auth'])->name('admin'); 

// Panel de Conductor
Route::get('/conductor', function () {
    return view('conductor');
})->name('conductor'); 

// layout Dashboard
Route::get('/admin/dashboard', function () {
    return view('adminDashboard');
})->name('adminDashboard');

// layout adminUsuarios
Route::get('/admin/usuarios', function () {
    return view('adminUsuarios');
})->name('adminUsuarios');

// layout adminConductores
Route::get('/admin/conductores', function () {
    return view('adminConductores');
})->name('adminConductores');

// layout adminClientes
Route::get('/admin/clientes', function () {
    return view('adminClientes');
})->name('adminClientes');

// layout adminViajes
Route::get('/admin/viajes', function () {
    return view('adminViajes');
})->name('adminViajes');

// Layout Precio
Route::get('/admin/pricing', function () {
    return view('adminPricing');
})->name('adminPricing');

