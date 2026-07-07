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
        $path = $request->getPathInfo();
        $ampersandPos = strpos($path, '/retour&');

        if ($ampersandPos === false) {
            return $next($request);
        }

        $basePath = substr($path, 0, $ampersandPos + strlen('/retour'));
        $bambooQuery = substr($path, $ampersandPos + strlen('/retour&'));
        $mergedQuery = $bambooQuery;

        if ($request->getQueryString() !== null && $request->getQueryString() !== '') {
            $mergedQuery = $request->getQueryString().'&'.$bambooQuery;
        }

        $request->server->set('REQUEST_URI', $basePath.'?'.$mergedQuery);
        $request->server->set('PATH_INFO', $basePath);
        $request->server->set('QUERY_STRING', $mergedQuery);

        parse_str($mergedQuery, $queryParams);
        $request->query->replace($queryParams);

        return $next($request);
    }
}
