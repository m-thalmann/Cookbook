<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use App\Traits\Models\QueryOrganizable;
use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TokenAuth\Traits\HasAuthTokens;

class User extends Authenticatable implements MustVerifyEmail {
    use HasFactory,
        Notifiable,
        HasAuthTokens,
        QueryOrganizable,
        SerializesDatesToTimestamp;

    const DEMO_EMAIL = 'demo@example.com';

    protected $fillable = ['name', 'email', 'password', 'language_code'];

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
    protected $sortableProperties = ['id', 'name', 'email', 'language_code'];
    protected $filterableProperties = ['name', 'email', 'language_code'];
    protected $searchProperties = ['name', 'email'];

    public function recipes() {
        return $this->hasMany(Recipe::class);
    }

    public function cookbooks() {
        return $this->belongsToMany(Cookbook::class, 'cookbook_user')
            ->using(CookbookUser::class)
            ->withPivot('is_admin')
            ->withTimestamps()
            ->as('meta');
    }

    public function scopeIsAdmin(Builder $query, bool $admin = true) {
        $query->where('is_admin', $admin);
    }

    public function preferredLocale() {
        return $this->language_code;
    }

    public function hasVerifiedEmail() {
        return !config('app.email_verification_enabled') ||
            parent::hasVerifiedEmail();
    }

    public function sendEmailVerificationNotification() {
        if (config('app.email_verification_enabled')) {
            $this->notify(new VerifyEmail());
        }
    }

    public function sendPasswordResetNotification($token) {
        $this->notify(new ResetPassword($token));
    }
}
