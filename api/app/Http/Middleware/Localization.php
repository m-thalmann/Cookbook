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
        $requestLanguage = $request->getPreferredLanguage();

        if ($requestLanguage) {
            $requestLanguage = Str::before($requestLanguage, '_');
        }

        // defines the priority of the set languages (null values are ignored)
        $preferredLanguages = [
            $request->query('lang'),
            $request->header('X-Language'),
            $requestLanguage,
        ];

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
}
