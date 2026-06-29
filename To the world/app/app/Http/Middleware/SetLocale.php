<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['en', 'pt'];

    private const COOKIE = 'INMACOM_LOCALE';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie(self::COOKIE);

        if (! in_array($locale, self::SUPPORTED, true)) {
            $user = $request->user();
            $preferred = is_array($user?->preferences ?? null)
                ? ($user->preferences['language'] ?? null)
                : null;

            if (in_array($preferred, self::SUPPORTED, true)) {
                $locale = $preferred;
            } else {
                $locale = config('app.locale', 'en');
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
