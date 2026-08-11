<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvidence extends Model
{
    protected $table = 'order_evidences'; // <-- agregar esta línea

    protected $fillable = ['order_id', 'type', 'photo_path', 'taken_by', 'taken_at'];

    protected function casts(): array
    {
        return ['taken_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function takenBy()
    {
        return $this->belongsTo(User::class, 'taken_by');
    }
}