<?php

namespace App\Http\Resources;

use App\Services\OrderBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cancellable = in_array($this->status, ['pending', 'assigned', 'en_route_pickup'], true);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'assignment_type' => $this->assignment_type,
            'clinic' => [
                'id' => $this->clinic->id,
                'business_name' => $this->clinic->business_name,
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
            'notes' => $this->notes,

            // Tarifa y reglas de negocio
            'distance_km' => $this->distance_km,
            'is_urgent' => (bool) $this->is_urgent,
            'urgent_surcharge' => $this->urgent_surcharge,
            'urgent_deadline_at' => $this->urgent_deadline_at,
            'quoted_price' => $this->quoted_price,
            'amount_paid' => $this->amount_paid,
            'amount_due' => max(0, round((float) $this->price - (float) $this->amount_paid, 2)),
            'refunded_amount' => $this->refunded_amount,
            'cancellation_fee' => $this->cancellation_fee,
            'return_surcharge' => $this->return_surcharge,
            'return_reason' => $this->return_reason,
            'can_cancel' => $cancellable,
            'cancel_fee_preview' => $cancellable
                ? app(OrderBillingService::class)->cancellationFee($this->resource)
                : null,

            'evidences' => $this->whenLoaded('evidences', fn () => $this->evidences->map(fn ($e) => [
                'type' => $e->type,
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