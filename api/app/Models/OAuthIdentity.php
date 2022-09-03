<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OAuthIdentity extends BaseModel {
    // use HasFactory; // TODO:

    protected $table = 'oauth_identities';

    protected $fillable = ['provider', 'provider_user_id'];

    protected $hidden = ['provider_user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}

