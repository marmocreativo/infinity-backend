<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Services\CreditService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /** Pedidos a crédito, saldo y vencimientos de la clínica */
    public function show(Request $request, CreditService $credit)
    {
        return response()->json($credit->summary($request->user()->clinic));
    }

    /** Paga uno o varios pedidos a crédito */
    public function pay(Request $request, CreditService $credit)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['integer'],
            'method' => ['required', 'in:card,oxxo'],
        ]);

        $clinic = $request->user()->clinic;
        $result = $credit->payOrders($clinic, $data['order_ids'], $data['method']);

        return response()->json($result + ['billing' => $credit->summary($clinic)]);
    }
}
