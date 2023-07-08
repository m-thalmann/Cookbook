<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Jenssegers\Agent\Agent;
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

        return [
            ...parent::toArray($request),
            'user_agent_details' => $this->getUserAgentDetails(),
            'is_current' => $isCurrent,
        ];
    }

    protected function getUserAgentDetails() {
        if ($this->user_agent !== null) {
            $agent = new Agent();
            $agent->setUserAgent($this->user_agent);

            return [
                'browser' => $this->getFullName($agent, $agent->browser()),
                'os' => $this->getFullName($agent, $agent->platform()),
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
            ];
        }

        return null;
    }

    protected function getFullName(Agent $agent, string|null $property) {
        if ($property === null) {
            return __('app.unknown');
        }

        $version = $agent->version($property);

        $fullName = $property;

        if ($version !== false) {
            $fullName .= ' ' . $version;
        }

        return $fullName;
    }
}
