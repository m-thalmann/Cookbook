<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Localization {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $preferredLanguages = $this->getPreferredLanguages($request);

        $selectedLanguage = null;

        foreach ($preferredLanguages as $preferredLanguage) {
            if (!$preferredLanguage) {
                continue;
            }

            if (is_dir(base_path("lang/$preferredLanguage"))) {
                $selectedLanguage = $preferredLanguage;
                break;
            }
        }

        if ($selectedLanguage !== null) {
            App::setLocale($selectedLanguage);
        }

        return $next($request);
    }

    /**
     * Returns the list of preferred languages in order of their priority.
     * May contain null-values.
     *
     * @param Request $request
     *
     * @return string[]
     */
    private function getPreferredLanguages(Request $request) {
        $requestLanguage = $request->getPreferredLanguage();

        if ($requestLanguage) {
            $requestLanguage = Str::before($requestLanguage, '_');
        }

        $preferredLanguages = [
            $request->query('lang'),
            $request->header('X-Language'),
        ];

        if (auth()->check()) {
            $preferredLanguages[] = authUser()->language_code;
        }

        $preferredLanguages[] = $requestLanguage;

        return $preferredLanguages;
    }
}
