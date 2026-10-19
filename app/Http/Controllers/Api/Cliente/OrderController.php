<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\Request;
use App\Services\PushNotificationService;
use App\Models\Payment;
use App\Services\OrderAssignmentService;
use App\Services\RouteQuoteService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->clinic->orders()
            ->with(['clinic', 'events'])
            ->with(['clinic', 'events', 'courier'])
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        return new OrderResource($order->load(['courier', 'evidences', 'events']));
    }

    public function quote(Request $request, RouteQuoteService $quoteService)
    {
        $data = $request->validate([
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'delivery_lat' => ['required', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json([
            'data' => $quoteService->quote(
                (float) $data['pickup_lat'],
                (float) $data['pickup_lng'],
                (float) $data['delivery_lat'],
                (float) $data['delivery_lng'],
            ),
        ]);
    }

    public function store(StoreOrderRequest $request, PushNotificationService $push, OrderAssignmentService $assignmentService, RouteQuoteService $quoteService)
    {
        $clinic = $request->user()->clinic;

        // El precio siempre se calcula en el servidor
        $quote = $quoteService->quote(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            (float) $request->delivery_lat,
            (float) $request->delivery_lng,
        );

        $order = Order::create([
            ...$request->validated(),
            'price' => $quote['price'],
            'clinic_id' => $clinic->id,
            'status' => 'pending',
            'assignment_type' => 'manual',
        ]);

        $order->refresh();

        Payment::create([
            'order_id' => $order->id,
            'amount' => $order->price,
            'gateway' => 'stripe',
            'status' => 'pending',
        ]);

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'created',
            'created_by' => $request->user()->id,
        ]);

        $wasAutoAssigned = $assignmentService->tryAutoAssign($order);

        if ($wasAutoAssigned) {
            $order->refresh();

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'assigned',
                'created_by' => null, // asignación automática, sin usuario humano
            ]);

            $push->sendToUser($order->courier, 'Pedido nuevo asignado', "Se te asignó automáticamente el pedido #{$order->id}.", [
                'order_id' => (string) $order->id,
                'type' => 'order_assigned',
            ]);
        } else {
            $push->sendToRole('admin', 'Nuevo pedido', "La clínica {$clinic->business_name} creó un pedido nuevo.", [
                'order_id' => (string) $order->id,
                'type' => 'order_created',
            ]);
        }

        return new OrderResource($order);
    }
}