<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedZone extends Model
{
    protected $fillable = ['name', 'polygon', 'is_active'];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
