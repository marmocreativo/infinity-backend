<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStop extends Model
{
    protected $fillable = [
        'order_id', 'type', 'sequence', 'address', 'lat', 'lng',
        'contact_name', 'contact_phone', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
