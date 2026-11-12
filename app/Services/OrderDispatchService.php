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
}
