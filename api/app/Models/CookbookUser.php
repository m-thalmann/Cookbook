<?php

namespace App\Models;

class CookbookUser extends BasePivot {
    protected $table = 'cookbook_user';

    protected $hidden = ['user_id', 'cookbook_id'];

    protected $casts = ['is_admin' => 'boolean'];

    public function cookbook() {
        return $this->belongsTo(Cookbook::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
