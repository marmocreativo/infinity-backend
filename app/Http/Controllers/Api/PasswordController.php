<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Support\Impersonation;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(ChangePasswordRequest $request)
    {
        abort_if(Impersonation::active($request), 403, 'No puedes cambiar la contraseña en modo "Ver como".');

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Contraseña actualizada.', 'user' => $user]);
    }
}