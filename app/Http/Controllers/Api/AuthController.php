<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterClienteRequest;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerCliente(RegisterClienteRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
        ]);

        $user->assignRole('cliente');

        Clinic::create([
            'user_id' => $user->id,
            'business_name' => $validated['business_name'],
            'tax_id' => $validated['tax_id'] ?? null,
            'default_address' => $validated['default_address'] ?? null,
            'default_lat' => $validated['default_lat'] ?? null,
            'default_lng' => $validated['default_lng'] ?? null,
        ]);

        $token = $user->createToken('cliente-app')->plainTextToken;

        return response()->json([
            'user' => $user->load('clinic'),
            'role' => 'cliente',
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Tu cuenta está inactiva o suspendida. Contacta al administrador.',
            ], 403);
        }

        $role = $user->getRoleNames()->first();

        $token = $user->createToken("{$role}-app")->plainTextToken;

        return response()->json([
            'user' => $user,
            'role' => $role,
            'must_change_password' => $user->must_change_password,
            'token' => $token,
        ]);
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me()
    {
        $user = request()->user();

        return response()->json([
            'user' => $user,
            'role' => $user->getRoleNames()->first(),
        ]);
    }
}