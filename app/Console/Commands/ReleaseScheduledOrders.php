<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Services\OrderAssignmentService;
use App\Services\OrderMailService;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

/**
 * Pedidos creados después de la hora límite quedan programados para el día siguiente.
 * Al llegar ese día, si la asignación es automática, se intentan asignar.
 * (En modo manual el admin los ve como pedidos por asignar.)
 */
class ReleaseScheduledOrders extends Command
{
    protected $signature = 'orders:release-scheduled';

    protected $description = 'Asigna automáticamente los pedidos programados cuyo día ya llegó';

    public function handle(
        OrderAssignmentService $assignment,
        PushNotificationService $push,
        OrderMailService $mail
    ): int {
        $today = now(config('service_rules.timezone'))->toDateString();

        $orders = Order::where('status', 'pending')
            ->whereNull('courier_id')
            ->whereNotNull('scheduled_for')
            ->whereDate('scheduled_for', '<=', $today)
            ->get();

        $assigned = 0;

        foreach ($orders as $order) {
            if ($order->awaitingPayment() || ! $assignment->tryAutoAssign($order)) {
                continue;
            }

            $order->refresh();

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'assigned',
                'created_by' => null,
            ]);

            $push->sendToUser($order->courier, 'Pedido nuevo asignado', "Se te asignó automáticamente el pedido #{$order->id}.", [
                'order_id' => (string) $order->id,
                'type' => 'order_assigned',
            ]);
            $mail->assigned($order);

            $assigned++;
        }

        $this->info("Pedidos programados: {$orders->count()}, asignados: {$assigned}");

        return self::SUCCESS;
    }
}
