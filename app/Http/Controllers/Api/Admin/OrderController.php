<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Request;
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

    public function assign(AssignOrderRequest $request, Order $order, PushNotificationService $push)
    {
        abort_unless($order->status === 'pending', 422, 'Solo se pueden asignar pedidos pendientes.');

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

        return new OrderResource($order->load(['clinic.user', 'courier', 'stops']));
    }
}