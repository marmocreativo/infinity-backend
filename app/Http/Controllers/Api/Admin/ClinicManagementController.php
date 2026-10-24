<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClinicRequest;
use App\Models\Clinic;

class ClinicManagementController extends Controller
{
    public function index()
    {
        $clinics = Clinic::with('user')->latest()->paginate(20);

        return response()->json($clinics);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic)
    {
        $clinic->user->update($request->only(['name', 'email', 'phone']));
        $clinic->update($request->only([
            'business_name', 'tax_id', 'default_address', 'default_lat', 'default_lng',
        ]));

        return response()->json($clinic->load('user'));
    }

    public function updateStatus(Clinic $clinic)
    {
        request()->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        $clinic->user->update(['status' => request('status')]);

        return response()->json($clinic->load('user'));
    }
}