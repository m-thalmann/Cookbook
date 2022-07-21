<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ingredient extends BaseModel {
    use HasFactory;

    protected $fillable = ['name', 'amount', 'unit', 'group'];

    protected $hidden = [];

    protected $casts = [];

    protected $attributes = [
        'amount' => null,
        'unit' => null,
        'group' => null,
    ];

    public function recipe() {
        return $this->belongsTo(Recipe::class);
    }
}

