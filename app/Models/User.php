<?php

namespace App\Models;

use App\Mail\PasswordResetMail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'photo_path',
        'status',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'photo_path',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /** Correo de recuperación con enlace a la app que corresponde al rol del usuario */
    public function sendPasswordResetNotification($token): void
    {
        $role = $this->getRoleNames()->first() ?? 'cliente';
        $base = config("apps.urls.{$role}") ?? config('apps.urls.cliente');
        $url = $base . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        $minutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        Mail::to($this->email)
            ->send(new PasswordResetMail($this->name, $url, $minutes));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function clinic()
    {
        return $this->hasOne(Clinic::class);
    }

    public function courierProfile()
    {
        return $this->hasOne(CourierProfile::class);
    }

    public function ordersAsCourier()
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function shifts()
    {
        return $this->hasMany(CourierShift::class, 'courier_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}