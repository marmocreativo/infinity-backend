<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierLocation extends Model
{
    protected $fillable = ['courier_id', 'shift_id', 'lat', 'lng', 'recorded_at'];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function shift()
    {
        return $this->belongsTo(CourierShift::class, 'shift_id');
    }
}