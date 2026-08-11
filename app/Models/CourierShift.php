<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierShift extends Model
{
    protected $fillable = ['courier_id', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function locations()
    {
        return $this->hasMany(CourierLocation::class, 'shift_id');
    }
}