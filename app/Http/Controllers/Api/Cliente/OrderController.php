<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Services\CreditService;
use App\Services\RedZoneService;
use App\Services\OrderDispatchService;
use App\Services\OrderAssignmentService;
use App\Services\OrderBillingService;
use App\Services\OrderMailService;
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
            ->with(['clinic', 'events', 'courier', 'stops'])
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        return new OrderResource($order->load(['courier', 'evidences', 'events', 'stops']));
    }

    /** Horario, repartidores en turno y tarifas vigentes */
    public function serviceStatus(Request $request, ServiceAvailabilityService $availability, CreditService $credit, RedZoneService $redZones)
    {
        $status = $availability->status();
        $clinic = $request->user()->clinic;
        $summary = $credit->summary($clinic);

        $status['credit_enabled'] = (bool) $clinic->credit_enabled;
        $status['credit_debt'] = $summary['debt'];

        // Con deuda de Crédito Infinity vencida no se pueden crear pedidos
        if ($summary['blocked']) {
            $status['can_order'] = false;
            $status['reason'] = 'debt';
            $status['message'] = 'Tienes un adeudo de Crédito Infinity de ' . number_format($summary['debt'], 2)
                . ' MXN. Cúbrelo para volver a hacer pedidos (revisa la sección Facturación).';
        }

        // Ubicación de la clínica dentro de una zona roja
        if ($message = $redZones->clinicBlockedMessage($clinic)) {
            $status['can_order'] = false;
            $status['reason'] = 'red_zone';
            $status['message'] = $message;
        }

        return response()->json($status);
    }

    /** Valida la recolección contra las zonas rojas (se llama al terminar el paso 1) */
    public function checkPickup(Request $request, RedZoneService $redZones)
    {
        $data = $request->validate([
            'pickup_lat' => ServiceArea::latRules(),
            'pickup_lng' => ServiceArea::lngRules(),
            'stops' => ['nullable', 'array', 'max:' . StoreOrderRequest::MAX_STOPS],
            'stops.*.type' => ['required', 'in:pickup,delivery'],
            'stops.*.lat' => ServiceArea::latRules(),
            'stops.*.lng' => ServiceArea::lngRules(),
        ]);

        $points = [[$data['pickup_lat'], $data['pickup_lng']]];
        foreach ($data['stops'] ?? [] as $stop) {
            if ($stop['type'] === 'pickup') {
                $points[] = [$stop['lat'], $stop['lng']];
            }
        }

        $blocked = $redZones->blockedMessage($request->user()->clinic, $points);
        abort_if($blocked !== null, 422, $blocked);

        return response()->json(['ok' => true]);
    }

    public function quote(Request $request, RouteQuoteService $quoteService, OrderBillingService $billing, RedZoneService $redZones)
    {
        $data = $request->validate([
            'pickup_lat' => ServiceArea::latRules(),
            'pickup_lng' => ServiceArea::lngRules(),
            'delivery_lat' => ServiceArea::latRules(),
            'delivery_lng' => ServiceArea::lngRules(),
            'stops' => ['nullable', 'array', 'max:' . StoreOrderRequest::MAX_STOPS],
            'stops.*.type' => ['required', 'in:pickup,delivery'],
            'stops.*.lat' => ServiceArea::latRules(),
            'stops.*.lng' => ServiceArea::lngRules(),
        ]);

        $pickupPoints = [[$data['pickup_lat'], $data['pickup_lng']]];
        foreach ($data['stops'] ?? [] as $stop) {
            if ($stop['type'] === 'pickup') {
                $pickupPoints[] = [$stop['lat'], $stop['lng']];
            }
        }
        $blocked = $redZones->blockedMessage($request->user()->clinic, $pickupPoints);
        abort_if($blocked !== null, 422, $blocked);

        $route = $quoteService->quoteWithStops(
            (float) $data['pickup_lat'],
            (float) $data['pickup_lng'],
            (float) $data['delivery_lat'],
            (float) $data['delivery_lng'],
            $data['stops'] ?? [],
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
        ServiceAvailabilityService $availability,
        CreditService $credit,
        OrderDispatchService $dispatcher,
        RedZoneService $redZones
    ) {
        $status = $availability->status();
        abort_unless($status['can_order'], 422, $status['message']);

        $clinic = $request->user()->clinic;

        // Zonas rojas: recolecciones y ubicación de la clínica
        $pickupPoints = [[$request->pickup_lat, $request->pickup_lng], [$clinic->default_lat, $clinic->default_lng]];
        foreach ($request->validated('stops') ?? [] as $stop) {
            if ($stop['type'] === 'pickup') {
                $pickupPoints[] = [$stop['lat'], $stop['lng']];
            }
        }
        $blocked = $redZones->blockedMessage($clinic, $pickupPoints);
        abort_if($blocked !== null, 422, $blocked);

        $method = $request->input('payment_method', 'card');
        abort_unless(in_array($method, ['card', 'oxxo', 'cash', 'credit'], true), 422, 'Método de pago no válido.');

        $debt = $credit->debt($clinic);
        abort_if($debt > 0, 422, 'Tienes un adeudo de Crédito Infinity de ' . number_format($debt, 2) . ' MXN. Cúbrelo para crear pedidos nuevos.');
        abort_if($method === 'credit' && ! $clinic->credit_enabled, 422, 'Tu clínica no tiene activo el Crédito Infinity.');
        $urgent = $request->boolean('is_urgent');

        $stops = $request->validated('stops') ?? [];

        $route = $quoteService->quoteWithStops(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            (float) $request->delivery_lat,
            (float) $request->delivery_lng,
            $stops,
        );
        $prices = $billing->breakdown($route['distance_km'], $urgent);

        $scheduledFor = $status['scheduled_for'] ?? null;

        $order = DB::transaction(function () use ($request, $clinic, $route, $urgent, $prices, $stops, $scheduledFor, $method) {
            $order = Order::create([
                ...collect($request->validated())->except('stops')->all(),
                'clinic_id' => $clinic->id,
                'status' => 'pending',
                'assignment_type' => 'manual',
                'scheduled_for' => $scheduledFor,
                'payment_method' => $method,
                'distance_km' => $route['distance_km'],
                'is_urgent' => $urgent,
                'requires_invoice' => $request->boolean('requires_invoice'),
                'urgent_surcharge' => $prices['urgent_surcharge'],
                'quoted_price' => $prices['total'],
                'price' => $prices['total'],
            ]);

            foreach (array_values($stops) as $i => $stop) {
                $order->stops()->create([
                    'type' => $stop['type'],
                    'sequence' => $i + 1,
                    'address' => $stop['address'],
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'contact_name' => $stop['contact_name'],
                    'contact_phone' => $stop['contact_phone'],
                ]);
            }

            return $order;
        });

        $order->refresh();

        Payment::create([
            'order_id' => $order->id,
            'amount' => $order->price,
            'gateway' => $method === 'credit' ? 'credit' : ($method === 'card' ? 'stripe' : $method),
            'status' => 'pending',
        ]);

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'created',
            'created_by' => $request->user()->id,
        ]);

        // El cobro se hace al confirmar el pedido
        $payment = $order->payment;

        if ($method === 'card') {
            // Simulación: aquí iría el cargo real a la pasarela (los datos de tarjeta nunca llegan al servidor)
            $order->update(['amount_paid' => $order->price, 'payment_status' => 'paid']);
            $payment->update([
                'status' => 'paid',
                'gateway_reference' => 'sim_' . \Illuminate\Support\Str::random(20),
                'paid_at' => now(),
            ]);
        } elseif ($method === 'oxxo') {
            $payment->update([
                'gateway' => 'oxxo',
                'gateway_reference' => (string) random_int(10000000, 99999999) . (string) random_int(100000, 999999),
            ]);
        }

        // OXXO: el pedido espera el depósito; se asigna y se avisa cuando se confirme el pago
        if (! $order->fresh()->awaitingPayment()) {
            $dispatcher->dispatch($order->fresh());
        }

        return new OrderResource($order->load(['clinic', 'stops']));
    }

    public function cancel(
        Request $request,
        Order $order,
        OrderBillingService $billing,
        PushNotificationService $push
    ) {
        abort_if($order->clinic_id !== $request->user()->clinic->id, 403);

        abort_unless(
            in_array($order->status, ['pending', 'assigned', 'en_route_pickup', 'arrived_pickup'], true),
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

        return new OrderResource($order->fresh(['clinic', 'courier', 'evidences', 'events', 'stops']));
    }
}