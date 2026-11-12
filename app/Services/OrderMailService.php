<?php

namespace App\Services;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Correos de confirmación de pedidos. Nunca deben romper el flujo del pedido:
 * si el servidor de correo falla, se registra el error y se continúa.
 */
class OrderMailService
{
    /** Pedido creado: confirmación a la clínica y aviso a los administradores */
    public function created(Order $order): void
    {
        $order = $this->prepare($order);
        $when = $order->scheduled_for
            ? ' Se programó para el ' . $order->scheduled_for->format('d/m/Y') . ' y se asignará a primera hora.'
            : '';

        $this->send(
            $order->clinic->user,
            $order,
            "Pedido #{$order->id} recibido",
            'Recibimos tu pedido',
            "Tu pedido #{$order->id} quedó registrado." . $when
        );

        foreach ($this->admins() as $admin) {
            $this->send(
                $admin,
                $order,
                "Nuevo pedido #{$order->id}",
                'Nuevo pedido registrado',
                "La clínica {$order->clinic->business_name} creó el pedido #{$order->id}." . $when
            );
        }
    }

    /** Pedido asignado: aviso al mensajero (y a la clínica) */
    public function assigned(Order $order): void
    {
        $order = $this->prepare($order);

        if ($order->courier) {
            $this->send(
                $order->courier,
                $order,
                "Pedido #{$order->id} asignado",
                'Se te asignó un pedido',
                "Tienes asignado el pedido #{$order->id}. Revisa los datos de recolección y entrega."
            );
        }

        $this->send(
            $order->clinic->user,
            $order,
            "Pedido #{$order->id}: mensajero asignado",
            'Ya tenemos mensajero para tu pedido',
            "El pedido #{$order->id} fue asignado" . ($order->courier ? " a {$order->courier->name}." : '.')
        );
    }

    /** Entrega completa: confirmación a clínica, mensajero y administradores */
    public function delivered(Order $order): void
    {
        $order = $this->prepare($order);

        $this->send(
            $order->clinic->user,
            $order,
            "Pedido #{$order->id} entregado",
            'Tu pedido fue entregado',
            "El pedido #{$order->id} se entregó correctamente en destino."
        );

        if ($order->courier) {
            $this->send(
                $order->courier,
                $order,
                "Entrega completada #{$order->id}",
                'Entrega completada',
                "Registraste la entrega del pedido #{$order->id}. ¡Gracias!"
            );
        }

        foreach ($this->admins() as $admin) {
            $this->send(
                $admin,
                $order,
                "Pedido #{$order->id} entregado",
                'Entrega completada',
                "El pedido #{$order->id} de {$order->clinic->business_name} fue entregado."
            );
        }
    }

    private function prepare(Order $order): Order
    {
        return $order->loadMissing(['clinic.user', 'courier', 'stops']);
    }

    private function admins()
    {
        return User::role('admin')->where('status', 'active')->get();
    }

    private function send(?User $to, Order $order, string $subject, string $heading, string $intro): void
    {
        if (! $to || ! $to->email) {
            return;
        }

        try {
            Mail::to($to->email)->send(new OrderNotificationMail($order, $subject, $heading, $intro));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
