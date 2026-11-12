<?php

namespace App\Http\Resources;

use App\Services\OrderBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cancellable = in_array($this->status, ['pending', 'assigned', 'en_route_pickup', 'arrived_pickup'], true);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'assignment_type' => $this->assignment_type,
            'clinic' => [
                'id' => $this->clinic->id,
                'business_name' => $this->clinic->business_name,
                // Datos de contacto de la clínica (solo cuando se cargó su usuario)
                'contact_name' => $this->clinic->relationLoaded('user') ? $this->clinic->user?->name : null,
                'phone' => $this->clinic->relationLoaded('user') ? $this->clinic->user?->phone : null,
                'email' => $this->clinic->relationLoaded('user') ? $this->clinic->user?->email : null,
            ],
            'courier' => $this->when($this->courier, fn () => [
                'id' => $this->courier->id,
                'name' => $this->courier->name,
                'photo_url' => $this->courier->photo_url,
            ]),
            'pickup' => [
                'address' => $this->pickup_address,
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
                'contact_name' => $this->pickup_contact_name,
                'contact_phone' => $this->pickup_contact_phone,
            ],
            'delivery' => [
                'address' => $this->delivery_address,
                'lat' => $this->delivery_lat,
                'lng' => $this->delivery_lng,
                'contact_name' => $this->delivery_contact_name,
                'contact_phone' => $this->delivery_contact_phone,
            ],
            'price' => $this->price,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'awaiting_payment' => $this->awaitingPayment(),
            // Ficha de pago OXXO (referencia simulada hasta integrar la pasarela real)
            'payment_reference' => $this->when(
                $this->payment_method === 'oxxo' && $this->payment_status !== 'paid',
                fn () => $this->payment?->gateway_reference
            ),
            'requires_invoice' => (bool) $this->requires_invoice,
            'scheduled_for' => $this->scheduled_for?->toDateString(),
            'sample_type' => $this->sample_type,
            'notes' => $this->notes,

            // Tarifa y reglas de negocio
            'distance_km' => $this->distance_km,
            'is_urgent' => (bool) $this->is_urgent,
            'urgent_surcharge' => $this->urgent_surcharge,
            'urgent_deadline_at' => $this->urgent_deadline_at,
            'quoted_price' => $this->quoted_price,
            'amount_paid' => $this->amount_paid,
            // A crédito no se cobra al momento: se factura a fin de mes
            'amount_due' => $this->payment_method === 'credit'
                ? 0
                : max(0, round((float) $this->price - (float) $this->amount_paid, 2)),
            'refunded_amount' => $this->refunded_amount,
            'cancellation_fee' => $this->cancellation_fee,
            'return_surcharge' => $this->return_surcharge,
            'return_reason' => $this->return_reason,
            'can_cancel' => $cancellable,
            'cancel_fee_preview' => $cancellable
                ? app(OrderBillingService::class)->cancellationFee($this->resource)
                : null,

            'stops' => $this->whenLoaded('stops', fn () => $this->stops->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'sequence' => $s->sequence,
                'address' => $s->address,
                'lat' => $s->lat,
                'lng' => $s->lng,
                'contact_name' => $s->contact_name,
                'contact_phone' => $s->contact_phone,
                'completed_at' => $s->completed_at,
            ])),
            'evidences' => $this->whenLoaded('evidences', fn () => $this->evidences->map(fn ($e) => [
                'type' => $e->type,
                'stop_id' => $e->stop_id,
                'photo_url' => asset('storage/' . $e->photo_path),
                'taken_at' => $e->taken_at,
            ])),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($ev) => [
                'event_type' => $ev->event_type,
                'created_at' => $ev->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}