<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClinicRequest;
use App\Models\Clinic;

class ClinicManagementController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $clinics = Clinic::with('user')->latest()->paginate(min((int) $request->input('per_page', 20), 200));

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

    /** Quita (o pone) la restricción de zonas rojas para esta clínica */
    public function updateRedZoneTrust(\Illuminate\Http\Request $request, Clinic $clinic)
    {
        $data = $request->validate(['trusted' => ['required', 'boolean']]);

        $clinic->update(['trust_red_zone' => $data['trusted']]);

        return response()->json($clinic->load('user'));
    }

    public function updateStatus(Clinic $clinic)
    {
        request()->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        $clinic->user->update(['status' => request('status')]);

        return response()->json($clinic->load('user'));
    }
}