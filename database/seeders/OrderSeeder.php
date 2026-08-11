<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();
        $courier = User::role('mensajero')->first();

        // --- Pedido 1: pendiente, sin asignar ---
        $order1 = Order::create([
            'clinic_id' => $clinic->id,
            'courier_id' => null,
            'status' => 'pending',
            'assignment_type' => 'manual',
            'pickup_address' => 'Av. Revolución 123, CDMX',
            'pickup_lat' => 19.347000,
            'pickup_lng' => -99.187000,
            'pickup_contact_name' => 'Recepción Clínica',
            'pickup_contact_phone' => '5500000002',
            'delivery_address' => 'Laboratorio Central, Av. Insurgentes 456, CDMX',
            'delivery_lat' => 19.360000,
            'delivery_lng' => -99.170000,
            'delivery_contact_name' => 'Recepción Laboratorio',
            'delivery_contact_phone' => '5500000099',
            'price' => 150.00,
            'payment_status' => 'pending',
        ]);

        OrderEvent::create([
            'order_id' => $order1->id,
            'event_type' => 'created',
            'created_by' => $clinic->user_id,
        ]);

        // --- Pedido 2: asignado, en ruta de recolección ---
        $order2 = Order::create([
            'clinic_id' => $clinic->id,
            'courier_id' => $courier->id,
            'status' => 'en_route_pickup',
            'assignment_type' => 'manual',
            'pickup_address' => 'Av. Revolución 123, CDMX',
            'pickup_lat' => 19.347000,
            'pickup_lng' => -99.187000,
            'pickup_contact_name' => 'Recepción Clínica',
            'pickup_contact_phone' => '5500000002',
            'delivery_address' => 'Laboratorio Central, Av. Insurgentes 456, CDMX',
            'delivery_lat' => 19.360000,
            'delivery_lng' => -99.170000,
            'delivery_contact_name' => 'Recepción Laboratorio',
            'delivery_contact_phone' => '5500000099',
            'price' => 180.00,
            'payment_status' => 'pending',
        ]);

        OrderEvent::create([
            'order_id' => $order2->id,
            'event_type' => 'created',
            'created_by' => $clinic->user_id,
        ]);
        OrderEvent::create([
            'order_id' => $order2->id,
            'event_type' => 'assigned',
            'created_by' => User::role('admin')->first()->id,
        ]);
        OrderEvent::create([
            'order_id' => $order2->id,
            'event_type' => 'en_route_pickup',
            'created_by' => $courier->id,
        ]);

        // --- Pedido 3: entregado y pagado (histórico) ---
        $order3 = Order::create([
            'clinic_id' => $clinic->id,
            'courier_id' => $courier->id,
            'status' => 'delivered',
            'assignment_type' => 'manual',
            'pickup_address' => 'Av. Revolución 123, CDMX',
            'pickup_lat' => 19.347000,
            'pickup_lng' => -99.187000,
            'delivery_address' => 'Laboratorio Central, Av. Insurgentes 456, CDMX',
            'delivery_lat' => 19.360000,
            'delivery_lng' => -99.170000,
            'price' => 200.00,
            'payment_status' => 'paid',
        ]);

        OrderEvent::create([
            'order_id' => $order3->id,
            'event_type' => 'created',
            'created_by' => $clinic->user_id,
        ]);
        OrderEvent::create([
            'order_id' => $order3->id,
            'event_type' => 'delivered',
            'created_by' => $courier->id,
        ]);
    }
}