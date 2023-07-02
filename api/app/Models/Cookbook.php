<?php

namespace App\Models;

use App\Traits\Models\QuerySearchable;
use App\Traits\Models\QuerySortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cookbook extends BaseModel {
    use HasFactory, QuerySortable, QuerySearchable;

    protected $fillable = ['name'];

    protected $hidden = [];

    protected $casts = [];

    /*
     * Organize properties
     */
    protected $sortableProperties = ['id', 'name'];
    protected $searchProperties = ['name'];

    public function recipes() {
        return $this->hasMany(Recipe::class);
    }

    public function users() {
        return $this->belongsToMany(User::class, 'cookbook_user')
            ->using(CookbookUser::class)
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
            $query->from('cookbook_user');
            $query
                ->whereColumn('cookbook_id', 'cookbooks.id')
                ->where('user_id', $user->id);

            if ($mustBeAdmin) {
                $query->where('is_admin', true);
            }
        });
    }
}
