<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'clinic_id', 'courier_id', 'status', 'assignment_type',
        'pickup_address', 'pickup_lat', 'pickup_lng',
        'pickup_contact_name', 'pickup_contact_phone',
        'delivery_address', 'delivery_lat', 'delivery_lng',
        'delivery_contact_name', 'delivery_contact_phone',
        'price', 'payment_status', 'notes',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function evidences()
    {
        return $this->hasMany(OrderEvidence::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}