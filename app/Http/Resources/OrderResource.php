<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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