<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;

/** Pone un pedido en operación: asignación automática, avisos y correos */
class OrderDispatchService
{
    public function __construct(
        private OrderAssignmentService $assignment,
        private PushNotificationService $push,
        private OrderMailService $mail,
    ) {
    }

    public function dispatch(Order $order): void
    {
        $order->loadMissing('clinic');

        $wasAutoAssigned = $this->assignment->tryAutoAssign($order);

        if ($wasAutoAssigned) {
            $this->afterAutoAssigned($order);
        } else {
            $title = $order->is_urgent ? 'Nuevo pedido URGENTE' : 'Nuevo pedido';

            $this->push->sendToRole('admin', $title, "La clínica {$order->clinic->business_name} creó un pedido nuevo.", [
                'order_id' => (string) $order->id,
                'type' => 'order_created',
            ]);
        }

        $this->mail->created($order);
        if ($wasAutoAssigned) {
            $this->mail->assigned($order);
        }
    }

    /**
     * Intenta asignar los pedidos que quedaron en espera (sin mensajero, ya sin
     * restricción de fecha, no pendientes de pago). Devuelve cuántos se asignaron.
     */
    public function assignWaiting(): int
    {
        $today = now(config('service_rules.timezone'))->toDateString();

        $orders = Order::where('status', 'pending')
            ->whereNull('courier_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('scheduled_for')->orWhereDate('scheduled_for', '<=', $today);
            })
            ->with('clinic.user')
            ->orderBy('id')
            ->get();

        $assigned = 0;

        foreach ($orders as $order) {
            if ($order->awaitingPayment() || ! $this->assignment->tryAutoAssign($order)) {
                continue;
            }

            $this->afterAutoAssigned($order);
            $this->mail->assigned($order->fresh());
            $assigned++;
        }

        return $assigned;
    }

    /** Evento, aviso al mensajero y aviso al cliente de que su pedido ya tiene mensajero */
    public function afterAutoAssigned(Order $order): void
    {
        $order->refresh();

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'assigned',
            'created_by' => null, // asignación automática, sin usuario humano
        ]);

        $this->push->sendToUser($order->courier, 'Pedido nuevo asignado', "Se te asignó automáticamente el pedido #{$order->id}.", [
            'order_id' => (string) $order->id,
            'type' => 'order_assigned',
        ]);

        $this->notifyClinicAssigned($order);
    }

    /** Avisa a la clínica que su pedido ya tiene mensajero */
    public function notifyClinicAssigned(Order $order): void
    {
        $order->loadMissing('clinic.user', 'courier');

        if (! $order->clinic?->user) {
            return;
        }

        $who = $order->courier?->name ? " ({$order->courier->name})" : '';

        $this->push->sendToUser($order->clinic->user, 'Tu pedido ya tiene mensajero', "El pedido #{$order->id} fue asignado{$who} y va en camino a recolectar.", [
            'order_id' => (string) $order->id,
            'type' => 'order_assigned_client',
        ]);
    }
}
