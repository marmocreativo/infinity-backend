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

        abort_if($order->payment_status === 'paid', 422, 'Este pedido ya fue pagado.');

        $payment = $order->payment;

        // Simulación: en producción esto vendría de un webhook real de Stripe
        $payment->update([
            'status' => 'paid',
            'gateway_reference' => 'sim_' . Str::random(20),
            'paid_at' => now(),
        ]);

        $order->update(['payment_status' => 'paid']);

        return response()->json([
            'message' => 'Pago simulado exitosamente.',
            'payment' => $payment,
        ]);
    }
}