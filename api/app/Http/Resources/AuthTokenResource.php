<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use TokenAuth\TokenAuth;

class AuthTokenResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        $isCurrent = match ($this->type === TokenAuth::TYPE_ACCESS) {
            true => $request->user()->currentToken()->id === $this->id,
            default => $request->user()->currentToken()->group_id ===
                $this->group_id,
        };

        return [...parent::toArray($request), 'is_current' => $isCurrent];
    }
}
