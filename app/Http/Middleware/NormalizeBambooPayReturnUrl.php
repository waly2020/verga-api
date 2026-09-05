<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeBambooPayReturnUrl
{
    /**
     * Bamboo Pay concatène parfois les paramètres avec & sans ? initial
     * (/paiement/PAY-XXX/retour&status=failed&ref=PAY-XXX), ce qui provoque un 404.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->server->get('REQUEST_URI', $request->getRequestUri());
        $ampersandPos = strpos($uri, '/retour&');

        if ($ampersandPos === false) {
            return $next($request);
        }

        $basePath = substr($uri, 0, $ampersandPos + strlen('/retour'));
        $bambooQuery = substr($uri, $ampersandPos + strlen('/retour&'));
        $mergedQuery = $bambooQuery;

        if ($request->getQueryString() !== null && $request->getQueryString() !== '') {
            $mergedQuery = $request->getQueryString().'&'.$bambooQuery;
        }

        $normalized = Request::create(
            $basePath.'?'.$mergedQuery,
            $request->getMethod(),
            [],
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent(),
        );
        $normalized->headers->replace($request->headers->all());

        app()->instance('request', $normalized);

        return $next($normalized);
    }
}
