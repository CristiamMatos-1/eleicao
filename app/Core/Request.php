<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(private string $baseUrl = '')
    {
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $qPos = strpos($uri, '?');
        $path = $qPos === false ? $uri : substr($uri, 0, $qPos);
        
        $base = rtrim($this->baseUrl, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . ltrim($path, '/');
        $path = str_replace('/public/index.php', '', $path);
        $path = str_replace('/public', '', $path);
        
        // Remove document root folder names that cPanel might inject in the URI
        // Example: if folder is public_html/voto or public_html/cristiammatos
        $path = preg_replace('#^/cristiammatos\.teo\.br#', '', $path);
        $path = preg_replace('#^/voto#', '', $path);
        $path = preg_replace('#^/ipccgv2#', '', $path);
        
        if ($path === '' || $path === '/') {
            return '/';
        }

        // Always ensure a leading slash for matching routes
        $path = '/' . ltrim($path, '/');

        // Keep the trailing slash if it exists so we can match it exactly or redirect it
        return $path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }
}