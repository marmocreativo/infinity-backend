<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditStatement extends Model
{
    protected $fillable = ['clinic_id', 'month', 'total', 'paid_at', 'note', 'registered_by'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
