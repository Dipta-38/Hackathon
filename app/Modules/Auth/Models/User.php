<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'dob',
        'address',
        'nid_no',
        'profile_photo_path',
        'email_verification_otp',
        'email_verification_expires_at',
        'login_otp',
        'login_otp_expires_at',
        'smart_contact_name_check',
        'otp_receiver_confirmation',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'dob' => 'date',
            'email_verification_expires_at' => 'datetime',
            'login_otp_expires_at' => 'datetime',
            'smart_contact_name_check' => 'boolean',
            'otp_receiver_confirmation' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function account()
    {
        return $this->hasOne(\App\Modules\Account\Models\Account::class, 'user_id');
    }

    public function profilePhotoUrl(): string
    {
        if (! $this->profile_photo_path) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'User');
        }

        return asset('storage/' . $this->profile_photo_path);
    }

    public function generateEmailVerificationOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->email_verification_otp = $otp;
        $this->email_verification_expires_at = now()->addMinutes(10);
        $this->save();

        return $otp;
    }

    public function generateLoginOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->login_otp = $otp;
        $this->login_otp_expires_at = now()->addMinutes(5);
        $this->save();

        return $otp;
    }
}
