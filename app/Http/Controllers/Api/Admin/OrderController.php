<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\OrderMailService;
use App\Services\OrderDispatchService;
use App\Services\PushNotificationService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['clinic.user', 'courier', 'stops'])->latest();

        if ($request->filled('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }

        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        return OrderResource::collection(
            $query->paginate(min((int) $request->input('per_page', 20), 100))
        );
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['clinic.user', 'courier', 'evidences', 'events', 'stops']));
    }

    /** Confirma manualmente un pago (p. ej. un depósito en OXXO) */
    public function markPaid(Order $order, OrderDispatchService $dispatcher)
    {
        abort_if($order->payment_method === 'credit', 422, 'Los pedidos a crédito se pagan por estado de cuenta mensual.');
        abort_if($order->payment_status === 'paid', 422, 'El pedido ya está pagado.');

        $wasWaiting = $order->awaitingPayment();

        $order->update(['amount_paid' => $order->price, 'payment_status' => 'paid']);
        $order->payment?->update(['amount' => $order->price, 'status' => 'paid', 'paid_at' => now()]);

        // Pedido que esperaba el depósito: ahora sí entra en operación
        if ($wasWaiting) {
            $dispatcher->dispatch($order->fresh());
        }

        return new OrderResource($order->load(['clinic.user', 'courier', 'stops']));
    }

    public function assign(AssignOrderRequest $request, Order $order, PushNotificationService $push)
    {
        abort_unless($order->status === 'pending', 422, 'Solo se pueden asignar pedidos pendientes.');
        abort_if($order->awaitingPayment(), 422, 'El pedido espera el pago por OXXO; confirma el pago antes de asignarlo.');

        $target = User::role('mensajero')->find($request->courier_id);
        abort_unless($target, 422, 'El usuario seleccionado no es un mensajero.');
        abort_unless($target->status === 'active', 422, 'No se puede asignar un pedido a un mensajero suspendido o inactivo.');

        $order->update([
            'courier_id' => $request->courier_id,
            'status' => 'assigned',
        ]);

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'assigned',
            'created_by' => $request->user()->id,
        ]);

        $courier = $order->courier;
        $push->sendToUser($courier, 'Pedido nuevo asignado', "Se te asignó el pedido #{$order->id}.", [
            'order_id' => (string) $order->id,
            'type' => 'order_assigned',
        ]);

        app(OrderMailService::class)->assigned($order);
        app(OrderDispatchService::class)->notifyClinicAssigned($order);

        return new OrderResource($order->load(['clinic.user', 'courier', 'stops']));
    }
}