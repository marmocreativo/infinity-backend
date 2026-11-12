<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedZone;
use App\Support\ServiceArea;
use Illuminate\Http\Request;

class RedZoneController extends Controller
{
    private function rules(bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$req, 'string', 'max:120'],
            'polygon' => [$req, 'array', 'min:3', 'max:200'],
            'polygon.*' => ['required', 'array', 'size:2'],
            'polygon.*.0' => ['required', 'numeric', 'between:' . ServiceArea::LAT_MIN . ',' . ServiceArea::LAT_MAX],
            'polygon.*.1' => ['required', 'numeric', 'between:' . ServiceArea::LNG_MIN . ',' . ServiceArea::LNG_MAX],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function index()
    {
        return response()->json(['data' => RedZone::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $zone = RedZone::create($request->validate($this->rules(true)));

        return response()->json($zone, 201);
    }

    public function update(Request $request, RedZone $redZone)
    {
        $redZone->update($request->validate($this->rules(false)));

        return response()->json($redZone);
    }

    public function destroy(RedZone $redZone)
    {
        $redZone->delete();

        return response()->json(['message' => 'Zona eliminada.']);
    }
}
