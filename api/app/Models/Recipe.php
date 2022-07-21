<?php

namespace App\Models;

use App\Services\HTMLPurifierService;
use App\Traits\Models\QueryOrganizable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends BaseModel {
    use HasFactory, SoftDeletes, QueryOrganizable;

    protected $fillable = [
        'user_id',
        'is_public',
        'language_code',
        'name',
        'description',
        'category',
        'portions',
        'difficulty',
        'preparation',
        'preparation_time_minutes',
        'resting_time_minutes',
        'cooking_time_minutes',
    ];

    protected $hidden = [];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $attributes = [
        'is_public' => false,
    ];

    /*
     * Organize properties
     */
    protected $sortableProperties = [
        'id',
        'name',
        'category',
        'difficulty',
        'preparation_time_minutes',
        'resting_time_minutes',
        'cooking_time_minutes',
        'created_at',
        'updated_at',
    ];
    protected $filterableProperties = [
        'user_id',
        'name',
        'category',
        'language_code',
        'category',
        'difficulty',
        'preparation_time_minutes',
        'resting_time_minutes',
        'cooking_time_minutes',
        'created_at',
        'updated_at',
    ];
    protected $searchProperties = ['name', 'description', 'category'];

    protected function preparation(): Attribute {
        return Attribute::make(
            set: fn($value) => app(HTMLPurifierService::class)->purify($value)
        );
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }
}

