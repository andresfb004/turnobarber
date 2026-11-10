<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('role')->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        // Un token vigente por dispositivo: al volver a iniciar sesión se reemplaza.
        $dispositivo = $request->validated('device_name') ?? 'api';
        $user->tokens()->where('name', $dispositivo)->delete();

        return response()->json([
            'token' => $user->createToken($dispositivo)->plainTextToken,
            'usuario' => [
                'nombre' => $user->name,
                'rol' => $user->role->nombre,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
