<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierProfile extends Model
{
    protected $fillable = ['user_id', 'vehicle_type', 'is_on_duty'];

    protected function casts(): array
    {
        return ['is_on_duty' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}