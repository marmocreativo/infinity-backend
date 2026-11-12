<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'tax_id',
        'default_address', 'default_lat', 'default_lng',
        'credit_enabled', 'trust_red_zone', 'credit_cycle_days',
    ];

    protected function casts(): array
    {
        return [
            'credit_enabled' => 'boolean',
            'trust_red_zone' => 'boolean',
            'credit_cycle_days' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}