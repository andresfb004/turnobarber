<?php

use App\Http\Controllers\Panel\AgendaController;
use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\CierreController;
use App\Http\Controllers\Panel\ClienteController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\ProfesionalController;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return redirect()->route(Auth::user()->esAdministrador() ? 'dashboard' : 'agenda');
})->name('inicio');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.intentar');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Administradores y profesionales: la agenda (cada profesional solo ve la suya).
    Route::middleware('rol:'.Role::ADMINISTRADOR.','.Role::PROFESIONAL)->group(function () {
        Route::get('agenda', [AgendaController::class, 'index'])->name('agenda');
        Route::patch('citas/{cita}/estado', [AgendaController::class, 'cambiarEstado'])->name('citas.estado');
    });

    // Solo administradores.
    Route::middleware('rol:'.Role::ADMINISTRADOR)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::post('citas', [AgendaController::class, 'store'])->name('citas.store');

        Route::resource('profesionales', ProfesionalController::class)
            ->parameters(['profesionales' => 'profesional'])
            ->except('show');
        Route::resource('cierres', CierreController::class)->except('show');
        Route::resource('clientes', ClienteController::class);
        Route::patch('clientes/{cliente}/desbloquear', [ClienteController::class, 'desbloquear'])
            ->name('clientes.desbloquear');
    });
});
