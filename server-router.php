<?php

/*
 | Production router for PHP's built-in web server (used on Railway).
 |
 | `php artisan serve` hands static files to the built-in server, which sends them with no
 | cache headers and no compression — so every page change re-downloaded the CSS and JS.
 | This router serves public files itself:
 |   - /build/assets/* (content-hashed by Vite): cached for a year, immutable
 |   - other public files: cached for a day, revalidated with an ETag
 |   - text files gzip-compressed
 | Everything else goes to Laravel, exactly like artisan serve.
 */

$public = realpath(__DIR__.'/public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$file = $uri !== '/' ? realpath($public.$uri) : false;
$allowedRoots = array_filter([$public, realpath(__DIR__.'/storage/app/public')]);
$insideAllowedRoot = $file !== false && array_filter($allowedRoots, fn ($root) => str_starts_with($file, $root.DIRECTORY_SEPARATOR)) !== [];

if ($insideAllowedRoot && is_file($file) && ! str_ends_with($file, '.php')) {
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'json' => 'application/json',
        'webmanifest' => 'application/manifest+json',
        'map' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'txt' => 'text/plain; charset=utf-8',
        'pdf' => 'application/pdf',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $etag = '"'.dechex(filemtime($file)).'-'.dechex(filesize($file)).'"';

    header('Content-Type: '.($types[$ext] ?? (mime_content_type($file) ?: 'application/octet-stream')));
    header('X-Content-Type-Options: nosniff');
    header('ETag: '.$etag);
    header('Cache-Control: '.match (true) {
        str_starts_with($uri, '/build/assets/') => 'public, max-age=31536000, immutable',
        $uri === '/sw.js' => 'no-cache',
        default => 'public, max-age=86400',
    });

    if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);

        return true;
    }

    $body = (string) file_get_contents($file);
    $compressible = in_array($ext, ['css', 'js', 'json', 'webmanifest', 'map', 'svg', 'txt'], true);

    header('Vary: Accept-Encoding');
    if ($compressible && strlen($body) > 1024 && function_exists('gzencode') && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')) {
        $body = gzencode($body, 6);
        header('Content-Encoding: gzip');
    }

    header('Content-Length: '.strlen($body));
    echo $body;

    return true;
}

require $public.'/index.php';
