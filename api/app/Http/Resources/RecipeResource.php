<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        $array = $this->resource
            ->makeHidden(['user_id', 'recipe_collection_id'])
            ->toArray();

        if (array_key_exists('ingredients', $array)) {
            $array['ingredients'] = collect($array['ingredients'])
                ->groupBy('group')
                ->map(fn($group, $key) => ['group' => $key, 'items' => $group])
                ->sortBy('group')
                ->values()
                ->toArray();
        }

        return $array;
    }
}

