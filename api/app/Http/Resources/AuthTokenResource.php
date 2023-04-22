<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use TokenAuth\TokenAuth;

class AuthTokenResource extends JsonResource {
    /**
     * @var array User-Agent information that should be parsed and displayed to the user
     *
     * @see https://github.com/nextcloud/server/blob/e5497d285be700e1f4953deb620cd3a5a1a2798b/apps/settings/src/components/AuthToken.vue
     */
    private const USER_AGENTS = [
        // Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
        'edge' =>
            "/^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+ Edge\/[0-9.]+$/",

        // Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
        'firefox' =>
            "/^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) Gecko\/[0-9.]+ Firefox\/([0-9.]+)$/",

        // Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
        'chrome' =>
            "/^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/([0-9.]+) (?:Mobile Safari|Safari)\/[0-9.]+$/",

        // Safari User Agent from http://www.useragentstring.com/pages/Safari/
        'safari' =>
            "/^Mozilla\/5\.0 \([^)]*(Windows|OS X)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)(?: Version\/([0-9.]+))? Safari\/[0-9.A-Z]+$/",

        // Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
        'androidChrome' => '/Android.*(?:; (.*) Build\/).*Chrome\/([0-9.]+)/',
    ];

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
            foreach (self::USER_AGENTS as $userAgent => $pattern) {
                if (preg_match($pattern, $this->user_agent, $matches) === 1) {
                    return [
                        'name' => __("app.browsers.$userAgent"),
                        'name_key' => $userAgent,
                        'os' => $matches[1],
                        'version' => $matches[2],
                    ];
                }
            }
        }

        return null;
    }
}
