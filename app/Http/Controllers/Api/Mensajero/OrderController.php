<?php

namespace App\Http\Controllers\Api\Mensajero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UploadEvidenceRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderEvidence;
use Illuminate\Http\Request;
use App\Services\OrderBillingService;
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

    public function reportProblem(
        Request $request,
        Order $order,
        OrderBillingService $billing,
        PushNotificationService $push
    ) {
        $this->authorizeCourier($request, $order);
        $this->assertStatus($order, ['picked_up', 'en_route_delivery']);

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'reason' => ['required', 'in:no_recipient,lab_closed,other'],
            'notes' => ['nullable', 'string', 'max:500', 'required_if:reason,other'],
        ], [
            'notes.required_if' => 'Describe el motivo del retorno.',
        ]);

        $labels = [
            'no_recipient' => 'Nadie recibió el pedido',
            'lab_closed' => 'El laboratorio estaba cerrado',
            'other' => 'Otro motivo',
        ];

        $reasonText = $labels[$data['reason']]
            . (! empty($data['notes']) ? ': ' . $data['notes'] : '');

        $path = $request->file('photo')->store('evidences/return', 'public');

        OrderEvidence::create([
            'order_id' => $order->id,
            'type' => 'return',
            'photo_path' => $path,
            'taken_by' => $request->user()->id,
            'taken_at' => now(),
        ]);

        // Cargo adicional por retorno (calcula saldo / reembolso según lo ya pagado)
        $billing->markReturned($order);

        $order->update([
            'status' => 'returned',
            'return_reason' => $reasonText,
        ]);
        $this->logEvent($order, 'returned', $request->user()->id);

        $push->sendToUser(
            $order->clinic->user,
            'No se pudo entregar tu pedido',
            "Pedido #{$order->id}: {$reasonText}. La muestra regresará a tu clínica y se aplicó un cargo adicional.",
            ['order_id' => (string) $order->id, 'type' => 'order_returned']
        );

        $push->sendToRole('admin', 'Pedido retornado a remitente', "Pedido #{$order->id}: {$reasonText}.", [
            'order_id' => (string) $order->id,
            'type' => 'order_returned',
        ]);

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

        $order->update([
            'status' => 'picked_up',
            // Pedido urgente: las horas corren desde la recolección
            'urgent_deadline_at' => $order->is_urgent
                ? now()->addHours((int) config('service_rules.urgent_max_hours'))
                : null,
        ]);
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