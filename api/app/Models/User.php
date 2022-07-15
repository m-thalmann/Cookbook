<?php

namespace App\Models;

use App\Traits\QueryOrganizable;
use App\Traits\SerializesDateToTimestamp;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TokenAuth\Traits\HasAuthTokens;

class User extends Authenticatable implements MustVerifyEmail {
    use HasFactory,
        Notifiable,
        HasAuthTokens,
        QueryOrganizable,
        SerializesDateToTimestamp;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'language_code',
    ];

    protected $hidden = [
        'password',
        'is_admin',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    protected $attributes = [
        'language_code' => null,
        'is_admin' => false,
        'email_verified_at' => null,
    ];

    /*
     * Organize properties
     */
    protected $sortableProperties = [
        'id',
        'first_name',
        'last_name',
        'language_code',
    ];
    protected $filterableProperties = [
        'first_name',
        'last_name',
        'language_code',
    ];
    protected $searchProperties = ['first_name', 'last_name'];

    public function hasVerifiedEmail() {
        return !config('app.email_verification_enabled') ||
            !is_null($this->email_verified_at);
    }

    public function sendEmailVerificationNotification() {
        if (config('app.email_verification_enabled')) {
            $this->notify(new VerifyEmail());
        }
    }
}
