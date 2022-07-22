<?php

namespace App\Models;

use App\Services\HTMLPurifierService;
use App\Traits\Models\QueryOrganizable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Recipe extends BaseModel {
    use HasFactory, SoftDeletes, QueryOrganizable, Prunable;

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

    protected $hidden = ['share_uuid'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $attributes = [
        'is_public' => false,
    ];

    protected $appends = ['thumbnail'];

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

    public function getThumbnailAttribute() {
        return $this->images()->first();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }

    public function images() {
        return $this->hasMany(RecipeImage::class);
    }

    public function prunable() {
        return static::where('deleted_at', '<', now()->subWeek());
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function (Recipe $recipe) {
            if ($recipe->isForceDeleting()) {
                Recipe::deleteImageFiles($recipe->query());
            }
        });
    }

    /**
     * Deletes all image files (not the entries in the database) for the recipes
     * found in the given query.
     * The query is **not** cloned before usage.
     *
     * @param \Illuminate\Database\Eloquent\Builder $recipesQuery
     * @return void
     */
    public static function deleteImageFiles($recipesQuery) {
        $imagePaths = $recipesQuery
            ->images()
            ->get('image_path')
            ->pluck('image_path')
            ->toArray();

        Storage::disk('public')->delete($imagePaths);
    }
}
