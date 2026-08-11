<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['clinic', 'courier'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        return OrderResource::collection($query->paginate(20));
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['clinic', 'courier', 'evidences', 'events']));
    }

    public function assign(AssignOrderRequest $request, Order $order)
    {
        abort_unless($order->status === 'pending', 422, 'Solo se pueden asignar pedidos pendientes.');

        $order->update([
            'courier_id' => $request->courier_id,
            'status' => 'assigned',
        ]);

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'assigned',
            'created_by' => $request->user()->id,
        ]);

        // TODO: notificar al mensajero (push) de pedido nuevo

        return new OrderResource($order->load('courier'));
    }
}