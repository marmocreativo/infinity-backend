<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->clinic->orders()
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        return new OrderResource($order->load(['courier', 'evidences', 'events']));
    }

    public function store(StoreOrderRequest $request)
    {
        $clinic = $request->user()->clinic;

        $order = Order::create([
            ...$request->validated(),
            'clinic_id' => $clinic->id,
            'status' => 'pending',
            'assignment_type' => 'manual',
        ]);

        $order->refresh(); // <-- agregar esta línea

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'created',
            'created_by' => $request->user()->id,
        ]);

        return new OrderResource($order);
    }
}