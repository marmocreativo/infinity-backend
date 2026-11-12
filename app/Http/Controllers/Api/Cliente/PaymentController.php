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

    /**
     * Pago de un pedido. Métodos:
     *  - card: tarjeta (SIMULADO; los datos de la tarjeta nunca llegan al servidor)
     *  - oxxo: genera una ficha de pago; queda pendiente hasta que se confirme el depósito
     *  - cash: se paga en efectivo al mensajero; se marca pagado al confirmar la entrega
     */
    public function pay(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        $data = $request->validate(['method' => ['required', 'in:card,oxxo,cash']]);
        $method = $data['method'];

        abort_if($order->payment_method === 'credit', 422, 'Este pedido va a Crédito Infinity y se cobra a fin de mes.');

        $due = round((float) $order->price - (float) $order->amount_paid, 2);
        abort_if($due <= 0, 422, 'Este pedido no tiene saldo pendiente.');
        abort_if(in_array($order->status, ['cancelled'], true), 422, 'Este pedido está cancelado.');

        $payment = $order->payment;
        $order->update(['payment_method' => $method]);

        if ($method === 'card') {
            // Simulación: en producción esto vendría de la pasarela de pagos
            $order->update(['amount_paid' => $order->price, 'payment_status' => 'paid']);

            $payment->update([
                'amount' => $order->price,
                'gateway' => 'card',
                'status' => 'paid',
                'gateway_reference' => 'sim_' . Str::random(20),
                'paid_at' => now(),
            ]);

            return response()->json(['message' => 'Pago con tarjeta realizado.']);
        }

        if ($method === 'oxxo') {
            $reference = $payment->gateway === 'oxxo' && $payment->gateway_reference
                ? $payment->gateway_reference
                : (string) random_int(10000000, 99999999) . (string) random_int(100000, 999999);

            $payment->update(['gateway' => 'oxxo', 'gateway_reference' => $reference, 'status' => 'pending']);

            return response()->json([
                'message' => 'Ficha OXXO generada.',
                'reference' => $reference,
            ]);
        }

        $payment->update(['gateway' => 'cash', 'gateway_reference' => null, 'status' => 'pending']);

        return response()->json(['message' => 'Pagarás en efectivo al mensajero.']);
    }
}
