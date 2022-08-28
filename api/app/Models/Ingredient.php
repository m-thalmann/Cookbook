<?php

namespace App\Models;

use App\Traits\Models\QuerySearchable;
use App\Traits\Models\QuerySortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ingredient extends BaseModel {
    use HasFactory, QuerySortable, QuerySearchable;

    protected $fillable = ['name', 'amount', 'unit', 'group'];

    protected $hidden = [];

    protected $casts = [];

    protected $attributes = [
        'amount' => null,
        'unit' => null,
        'group' => null,
    ];

    /*
     * Organize properties
     */
    protected $sortableProperties = ['id', 'name', 'amount', 'unit', 'group'];
    protected $searchProperties = ['name'];

    public function recipe() {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Returns whether no other ingredient with the given name
     * and group name exists for the given recipe.
     *
     * @param \App\Models\Recipe $recipe
     * @param string $name
     * @param string|null $group
     *
     * @return boolean
     */
    public static function isUniqueInRecipe($recipe, $name, $group) {
        $query = Ingredient::query()
            ->where('recipe_id', $recipe->id)
            ->where('name', $name);

        if ($group === null) {
            $query->whereNull('group');
        } else {
            $query->where('group', $group);
        }

        return !$query->exists();
    }

    public function getSortableProperties() {
        return $this->sortableProperties;
    }
    public function getSearchProperties() {
        return $this->searchProperties;
    }
}

