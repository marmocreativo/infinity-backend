<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($validated);

        return response()->json(['data' => $user->fresh()]);
    }
}