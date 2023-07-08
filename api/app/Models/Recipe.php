<?php

namespace App\Models;

use App\Services\HTMLPurifierService;
use App\Traits\Models\QueryOrganizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Recipe extends BaseModel {
    use HasFactory, SoftDeletes, QueryOrganizable, Prunable;

    protected $fillable = [
        'user_id',
        'cookbook_id',
        'is_public',
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

    public function cookbook() {
        return $this->belongsTo(Cookbook::class);
    }

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }

    public function images() {
        return $this->hasMany(RecipeImage::class);
    }

    public function thumbnail() {
        return $this->hasOne(RecipeImage::class)->where('id', function (
            $query
        ) {
            $query
                ->selectRaw('min(ri.id)')
                ->from('recipe_images as ri')
                ->whereColumn('ri.recipe_id', 'recipe_images.recipe_id');
        });
    }

    public function scopeForUser(
        Builder $query,
        ?User $user,
        bool $all = false
    ) {
        $query->where(function (Builder $query) use ($user, $all) {
            $hasUser = $user !== null;
            $isAdminUser = $hasUser && $user->is_admin;

            if ($all && $isAdminUser) {
                return;
            }

            if ($hasUser) {
                $query->where('user_id', auth()->id());
            }

            if (!$hasUser || $all) {
                $query->orWhere('is_public', true);
            }

            if ($hasUser && $all) {
                $query->orWhereHas('cookbook', function ($query) {
                    $query->whereRelation('users', 'user_id', auth()->id());
                });
            }
        });
    }

    public function scopeCategories(Builder $query, ?string $sort) {
        $sortByAmount =
            $sort !== null &&
            preg_match_all('/^-?amount$/', $sort, $matches, PREG_SET_ORDER, 0);

        $query->select('category');

        if ($sortByAmount) {
            $sortDir = $sort[0] === '-' ? 'desc' : 'asc';

            $query
                ->groupBy('category')
                ->selectRaw('count(*) as amount')
                ->orderBy('amount', $sortDir);
        } else {
            $query->orderBy('id')->distinct();
        }

        $query->orderBy('category', 'asc');
    }

    public function scopeIsPublic(Builder $query, bool $public = true) {
        $query->where('is_public', $public);
    }

    public function prunable() {
        return static::where('deleted_at', '<', now()->subWeek());
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function (Recipe $recipe) {
            if ($recipe->isForceDeleting()) {
                Recipe::deleteImageFiles(
                    Recipe::query()
                        ->where('id', $recipe->id)
                        ->withTrashed()
                );
            }
        });
    }

    /**
     * Deletes all image files (not the entries in the database) for the recipes
     * found in the given query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $recipesQuery
     * @return void
     */
    public static function deleteImageFiles($recipesQuery) {
        $recipeIds = $recipesQuery
            ->clone()
            ->get('id')
            ->pluck('id')
            ->toArray();

        clock('recipeIds', $recipeIds);

        if (count($recipeIds) === 0) {
            return;
        }

        $imagePaths = RecipeImage::query()
            ->whereHas('recipe', function ($query) use ($recipeIds) {
                $query->whereIn('id', $recipeIds)->withTrashed();
            })
            ->get('image_path')
            ->pluck('image_path')
            ->toArray();

        clock('imagePaths', $imagePaths);

        if (count($imagePaths) === 0) {
            return;
        }

        Storage::disk('public')->delete($imagePaths);
    }
}
