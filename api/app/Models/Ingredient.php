<?php

namespace App\Models;

use App\Traits\Models\QuerySearchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ingredient extends BaseModel {
    use HasFactory, QuerySearchable;

    protected $fillable = ['name', 'amount', 'unit', 'group'];

    protected $hidden = [];

    protected $casts = [];

    protected $attributes = [
        'amount' => null,
        'unit' => null,
        'group' => null,
    ];

    protected $searchProperties = ['name'];

    public function recipe() {
        return $this->belongsTo(Recipe::class);
    }
}

