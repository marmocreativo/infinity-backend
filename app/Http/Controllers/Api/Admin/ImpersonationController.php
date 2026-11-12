<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /** Horas de vida de una sesión "Ver como" */
    private const TTL_HOURS = 2;

    public function store(Request $request, User $user)
    {
        $admin = $request->user();

        abort_if($user->hasRole('admin'), 422, 'No se puede entrar como otro administrador.');
        abort_unless($user->status === 'active', 422, 'Solo se puede entrar como un usuario activo.');

        $role = $user->getRoleNames()->first();
        abort_unless(in_array($role, ['cliente', 'mensajero'], true), 422, 'Este usuario no tiene un rol válido.');

        // El nombre del token marca la sesión como "Ver como" y guarda quién la inició
        $token = $user->createToken(
            'impersonation:' . $admin->id,
            ['*'],
            now()->addHours(self::TTL_HOURS)
        )->plainTextToken;

        Log::info('Impersonación iniciada', [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return response()->json([
            'user' => $user->load($role === 'cliente' ? 'clinic' : 'courierProfile'),
            'role' => $role,
            'must_change_password' => false,
            'token' => $token,
            'app_url' => config("apps.urls.{$role}"),
            'expires_in_minutes' => self::TTL_HOURS * 60,
            'impersonated_by' => $admin->name,
        ]);
    }
}
