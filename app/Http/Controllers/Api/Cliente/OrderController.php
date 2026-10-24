<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Services\OrderAssignmentService;
use App\Services\OrderBillingService;
use App\Services\PushNotificationService;
use App\Services\RouteQuoteService;
use App\Services\ServiceAvailabilityService;
use App\Support\ServiceArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->clinic->orders()
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

    /** Horario, repartidores en turno y tarifas vigentes */
    public function serviceStatus(ServiceAvailabilityService $availability)
    {
        return response()->json($availability->status());
    }

    public function quote(Request $request, RouteQuoteService $quoteService, OrderBillingService $billing)
    {
        $data = $request->validate([
            'pickup_lat' => ServiceArea::latRules(),
            'pickup_lng' => ServiceArea::lngRules(),
            'delivery_lat' => ServiceArea::latRules(),
            'delivery_lng' => ServiceArea::lngRules(),
        ]);

        $route = $quoteService->quote(
            (float) $data['pickup_lat'],
            (float) $data['pickup_lng'],
            (float) $data['delivery_lat'],
            (float) $data['delivery_lng'],
        );

        // El front suma el cargo urgente si el cliente lo activa
        $prices = $billing->breakdown($route['distance_km'], false);

        return response()->json([
            'data' => array_merge($route, [
                'route_price' => $prices['route_price'],
                'urgent_surcharge' => (float) config('service_rules.urgent_surcharge'),
            ]),
        ]);
    }

    public function store(
        StoreOrderRequest $request,
        PushNotificationService $push,
        OrderAssignmentService $assignmentService,
        RouteQuoteService $quoteService,
        OrderBillingService $billing,
        ServiceAvailabilityService $availability
    ) {
        $status = $availability->status();
        abort_unless($status['can_order'], 422, $status['message']);

        $clinic = $request->user()->clinic;
        $urgent = $request->boolean('is_urgent');

        $route = $quoteService->quote(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            (float) $request->delivery_lat,
            (float) $request->delivery_lng,
        );
        $prices = $billing->breakdown($route['distance_km'], $urgent);

        $order = Order::create([
            ...$request->validated(),
            'clinic_id' => $clinic->id,
            'status' => 'pending',
            'assignment_type' => 'manual',
            'distance_km' => $route['distance_km'],
            'is_urgent' => $urgent,
            'urgent_surcharge' => $prices['urgent_surcharge'],
            'quoted_price' => $prices['total'],
            'price' => $prices['total'],
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
            $title = $urgent ? 'Nuevo pedido URGENTE' : 'Nuevo pedido';

            $push->sendToRole('admin', $title, "La clínica {$clinic->business_name} creó un pedido nuevo.", [
                'order_id' => (string) $order->id,
                'type' => 'order_created',
            ]);
        }

        return new OrderResource($order);
    }

    public function cancel(
        Request $request,
        Order $order,
        OrderBillingService $billing,
        PushNotificationService $push
    ) {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        abort_unless(
            in_array($order->status, ['pending', 'assigned', 'en_route_pickup'], true),
            422,
            'Este pedido ya no se puede cancelar porque la muestra ya fue recolectada.'
        );

        $courier = $order->courier;

        DB::transaction(function () use ($order, $billing, $request) {
            // Primero el cobro (depende del estado actual) y luego el cambio de estado
            $billing->cancel($order);

            $order->update(['status' => 'cancelled']);

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'cancelled',
                'created_by' => $request->user()->id,
            ]);
        });

        if ($courier) {
            $push->sendToUser($courier, 'Pedido cancelado', "El pedido #{$order->id} fue cancelado por la clínica.", [
                'order_id' => (string) $order->id,
                'type' => 'order_cancelled',
            ]);
        }

        $push->sendToRole('admin', 'Pedido cancelado', "La clínica canceló el pedido #{$order->id}.", [
            'order_id' => (string) $order->id,
            'type' => 'order_cancelled',
        ]);

        return new OrderResource($order->fresh(['clinic', 'courier', 'evidences', 'events']));
    }
}