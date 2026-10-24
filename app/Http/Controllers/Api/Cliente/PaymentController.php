<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function show(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        return response()->json($order->payment);
    }

    public function pay(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        $due = round((float) $order->price - (float) $order->amount_paid, 2);

        abort_if($due <= 0, 422, 'Este pedido no tiene saldo pendiente.');

        $payment = $order->payment;

        // Simulación: en producción esto vendría de un webhook real de Stripe
        $order->update([
            'amount_paid' => $order->price,
            'payment_status' => 'paid',
        ]);

        $payment->update([
            'amount' => $order->price,
            'status' => 'paid',
            'gateway_reference' => 'sim_' . Str::random(20),
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pago simulado exitosamente.',
            'payment' => $payment,
        ]);
    }
}