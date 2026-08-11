<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UploadEvidenceRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderEvidence;
use Illuminate\Http\Request;
use App\Services\PushNotificationService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('courier_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->courier_id !== $request->user()->id, 403);

        return new OrderResource($order->load(['clinic', 'evidences', 'events']));
    }

    public function startPickup(Request $request, Order $order)
    {
        $this->authorizeCourier($request, $order);
        $this->assertStatus($order, ['assigned']);

        $order->update(['status' => 'en_route_pickup']);
        $this->logEvent($order, 'en_route_pickup', $request->user()->id);

        return new OrderResource($order);
    }

    public function confirmPickup(UploadEvidenceRequest $request, Order $order, PushNotificationService $push)
    {
        $this->authorizeCourier($request, $order);
        $this->assertStatus($order, ['en_route_pickup']);

        $path = $request->file('photo')->store('evidences/pickup', 'public');

        OrderEvidence::create([
            'order_id' => $order->id,
            'type' => 'pickup',
            'photo_path' => $path,
            'taken_by' => $request->user()->id,
            'taken_at' => now(),
        ]);

        $order->update(['status' => 'picked_up']);
        $this->logEvent($order, 'picked_up', $request->user()->id);

        $push->sendToUser($order->clinic->user, 'Muestra recolectada', "Tu pedido #{$order->id} fue recolectado.", [
            'order_id' => (string) $order->id,
            'type' => 'order_picked_up',
        ]);

        return new OrderResource($order->load('evidences'));
    }

    public function confirmDelivery(UploadEvidenceRequest $request, Order $order, PushNotificationService $push)
    {
        $this->authorizeCourier($request, $order);
        $this->assertStatus($order, ['en_route_delivery']);

        $path = $request->file('photo')->store('evidences/delivery', 'public');

        OrderEvidence::create([
            'order_id' => $order->id,
            'type' => 'delivery',
            'photo_path' => $path,
            'taken_by' => $request->user()->id,
            'taken_at' => now(),
        ]);

        $order->update(['status' => 'delivered']);
        $this->logEvent($order, 'delivered', $request->user()->id);

        $push->sendToUser($order->clinic->user, 'Pedido entregado', "Tu pedido #{$order->id} fue entregado en destino.", [
            'order_id' => (string) $order->id,
            'type' => 'order_delivered',
        ]);

        return new OrderResource($order->load('evidences'));
    }

    public function startDelivery(Request $request, Order $order)
    {
        $this->authorizeCourier($request, $order);
        $this->assertStatus($order, ['picked_up']);

        $order->update(['status' => 'en_route_delivery']);
        $this->logEvent($order, 'en_route_delivery', $request->user()->id);

        return new OrderResource($order);
    }

    private function authorizeCourier(Request $request, Order $order): void
    {
        abort_if($order->courier_id !== $request->user()->id, 403, 'Este pedido no está asignado a ti.');
    }

    private function assertStatus(Order $order, array $allowed): void
    {
        abort_unless(in_array($order->status, $allowed), 422, "No se puede realizar esta acción desde el estado actual: {$order->status}");
    }

    private function logEvent(Order $order, string $eventType, int $userId): void
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'created_by' => $userId,
        ]);
    }
}