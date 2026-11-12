<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProfesionalController;
use App\Http\Controllers\Api\RecordatorioController;
use App\Http\Controllers\Api\TurnoController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.login');

Route::middleware(['auth:sanctum', 'rol:'.Role::BOT.','.Role::ADMINISTRADOR, 'liberar.bloqueos'])
    ->name('api.')
    ->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('profesionales', ProfesionalController::class)
            ->only(['index', 'show'])
            ->parameters(['profesionales' => 'profesional']);
        Route::get('turnos', TurnoController::class)->name('turnos');

        Route::post('clientes/vincular', [ClienteController::class, 'vincular'])->name('clientes.vincular');
        Route::get('clientes/{telegramId}/citas', [ClienteController::class, 'citas'])
            ->whereNumber('telegramId')
            ->name('clientes.citas');

        Route::apiResource('citas', CitaController::class)->only(['store', 'show', 'update']);
        Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar');

        Route::get('recordatorios/pendientes', [RecordatorioController::class, 'pendientes'])
            ->name('recordatorios.pendientes');
        Route::patch('recordatorios/{cita}/enviado', [RecordatorioController::class, 'enviado'])
            ->name('recordatorios.enviado');

        // Escrituras de administración: el bot no puede crear profesionales ni borrar citas.
        Route::middleware('rol:'.Role::ADMINISTRADOR)->group(function () {
            Route::apiResource('profesionales', ProfesionalController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameters(['profesionales' => 'profesional']);
            Route::apiResource('citas', CitaController::class)->only(['destroy']);
        });
    });
