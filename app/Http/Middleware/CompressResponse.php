<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzip HTML and JSON responses. The hosting edge does not compress, so pages travelled
 * uncompressed — on a phone that is a visible part of every page change.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse
            || ! function_exists('gzencode')
            || $response->headers->has('Content-Encoding')
            || ! str_contains((string) $request->headers->get('Accept-Encoding'), 'gzip')
            || ! preg_match('#^(text/html|application/json)#', (string) $response->headers->get('Content-Type'))) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < 1024) {
            return $response;
        }

        $response->setContent(gzencode($content, 5));
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($response->getContent()));
        $response->setVary(array_unique([...$response->getVary(), 'Accept-Encoding']));

        return $response;
    }
}
