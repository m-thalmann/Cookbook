<?php

namespace App\Models;

class RecipeCollectionUser extends BasePivot {
    protected $table = 'recipe_collection_user';

    protected $hidden = ['user_id', 'recipe_collection_id'];

    protected $casts = ['is_admin' => 'boolean'];

    public function recipeCollection() {
        return $this->belongsTo(RecipeCollection::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}

