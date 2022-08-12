<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecipeCollection extends BaseModel {
    use HasFactory;

    protected $fillable = ['name'];

    protected $hidden = [];

    protected $casts = [];

    public function recipes() {
        return $this->hasMany(Recipe::class);
    }

    public function users() {
        return $this->belongsToMany(User::class, 'recipe_collection_user')
            ->using(RecipeCollectionUser::class)
            ->withPivot('is_admin')
            ->withTimestamps()
            ->as('meta');
    }

    public function scopeForUser(
        $query,
        User $user,
        bool $mustBeAdmin = false
    ) {
        $query->whereExists(function ($query) use ($user, $mustBeAdmin) {
            $query->from('recipe_collection_user');
            $query
                ->whereColumn('recipe_collection_id', 'recipe_collections.id')
                ->where('user_id', $user->id);

            if ($mustBeAdmin) {
                $query->where('is_admin', true);
            }
        });
    }
}

