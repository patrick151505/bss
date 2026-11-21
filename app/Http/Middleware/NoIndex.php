<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the whole system out of search engines (Google, Bing, …): every
 * response — pages, printouts, PDFs, file downloads, JSON — carries
 * "X-Robots-Tag: noindex". Static files served directly by the web server
 * get the same header from public/.htaccess, and robots.txt blocks crawling.
 */
class NoIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');

        return $response;
    }
}
