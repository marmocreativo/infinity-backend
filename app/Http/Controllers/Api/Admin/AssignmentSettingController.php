<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSetting;
use Illuminate\Http\Request;

class AssignmentSettingController extends Controller
{
    public function show()
    {
        $setting = AssignmentSetting::first() ?? AssignmentSetting::create(['mode' => 'manual']);

        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $request->validate(['mode' => ['required', 'in:manual,automatic']]);

        $setting = AssignmentSetting::first() ?? new AssignmentSetting();
        $setting->mode = $request->mode;
        $setting->save();

        return response()->json($setting);
    }
}