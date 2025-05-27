<?php

namespace App\Middlewares;

use App\Services\Translations\I18n;
use Core\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware implements MiddlewareInterface
{
    private I18n $i18n;

    public function __construct(I18n $i18n)
    {
        $this->i18n = $i18n;
    }

    public function handle(Request $request, callable $next): Response
    {
        $locale = $this->i18n->detectLocale($request);
        $this->i18n->setLocale($locale);

        return $next($request);
    }
}