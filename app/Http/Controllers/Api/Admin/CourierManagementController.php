<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourierRequest;
use App\Http\Requests\Admin\UpdateCourierRequest;
use App\Models\CourierProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CourierManagementController extends Controller
{
    public function index()
    {
        $couriers = User::role('mensajero')
            ->with('courierProfile')
            ->latest()
            ->paginate(20);

        return response()->json($couriers);
    }

    public function store(StoreCourierRequest $request)
    {
        $plainPassword = Str::random(10);

        $courier = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($plainPassword),
            'phone' => $request->phone,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $courier->assignRole('mensajero');

        CourierProfile::create([
            'user_id' => $courier->id,
            'vehicle_type' => $request->vehicle_type,
            'is_on_duty' => false,
        ]);

        return response()->json([
            'user' => $courier->load('courierProfile'),
            'credentials' => [
                'email' => $courier->email,
                'password' => $plainPassword,
            ],
        ], 201);
    }

    public function update(UpdateCourierRequest $request, User $courier)
    {
        $courier->update($request->only(['name', 'email', 'phone']));

        if ($request->filled('vehicle_type')) {
            $courier->courierProfile->update(['vehicle_type' => $request->vehicle_type]);
        }

        return response()->json($courier->load('courierProfile'));
    }

    public function updateStatus(User $courier)
    {
        request()->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        $courier->update(['status' => request('status')]);

        return response()->json($courier);
    }
}