<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditController extends Controller
{
    public function show(Clinic $clinic, CreditService $credit)
    {
        return response()->json($credit->summary($clinic));
    }

    /** Activa/desactiva el crédito y/o define el plazo máximo (7 o 15 días) */
    public function update(Request $request, Clinic $clinic, CreditService $credit)
    {
        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'cycle_days' => ['sometimes', Rule::in(CreditService::CYCLES)],
        ]);

        $clinic->update(array_filter([
            'credit_enabled' => $data['enabled'] ?? null,
            'credit_cycle_days' => $data['cycle_days'] ?? null,
        ], fn ($v) => $v !== null));

        return response()->json($credit->summary($clinic->fresh()));
    }

    /** Registra el pago de uno o varios pedidos */
    public function pay(Request $request, Clinic $clinic, CreditService $credit)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['integer'],
        ]);

        $credit->registerPayment($clinic, $data['order_ids']);

        return response()->json($credit->summary($clinic->fresh()));
    }
}
