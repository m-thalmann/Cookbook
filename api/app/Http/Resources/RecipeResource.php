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
        $array = $this->resource->toArray();

        if (array_key_exists('ingredients', $array)) {
            // group ingredients

            $array['ingredients'] = collect($array['ingredients'])
                ->groupBy('group')
                ->map(
                    fn($group, $key) => [
                        'group' => empty($key) ? null : $key,
                        'items' => $group,
                    ]
                )
                ->sortBy('group')
                ->values()
                ->toArray();
        }

        if (authUser()) {
            $array['user_can_edit'] =
                authUser()->can('update', $this->resource) || false;
        }

        return $array;
    }
}

