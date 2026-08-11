<?php

namespace App\Events;

use App\Models\CourierLocation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public CourierLocation $location)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.courier-tracking'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'courier.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'courier_id' => $this->location->courier_id,
            'shift_id' => $this->location->shift_id,
            'lat' => $this->location->lat,
            'lng' => $this->location->lng,
            'recorded_at' => $this->location->recorded_at,
        ];
    }
}