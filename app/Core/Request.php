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

        $path = '/' . ltrim($path, '/');

        // Remove front-controller segments when present in the URI.
        $path = preg_replace('#^/index\.php#', '', $path) ?? $path;
        $path = preg_replace('#^/public/index\.php#', '', $path) ?? $path;
        $path = preg_replace('#^/public#', '', $path) ?? $path;

        // Resolve base prefixes from config and server runtime (cPanel subfolder setups).
        $prefixes = [];
        $cfgBase = rtrim($this->baseUrl, '/');
        if ($cfgBase !== '') {
            $prefixes[] = $cfgBase;
        }

        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && $scriptDir !== '.') {
            $prefixes[] = $scriptDir;
        }

        $phpSelfDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '')), '/');
        if ($phpSelfDir !== '' && $phpSelfDir !== '.') {
            $prefixes[] = $phpSelfDir;
        }

        foreach (array_unique($prefixes) as $prefix) {
            if ($prefix === '' || $prefix === '/') {
                continue;
            }
            if ($path === $prefix) {
                $path = '/';
                break;
            }
            if (str_starts_with($path, $prefix . '/')) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        // Run again after prefix normalization for URLs like /subfolder/public/index.php/login
        $path = preg_replace('#^/public/index\.php#', '', $path) ?? $path;
        $path = preg_replace('#^/index\.php#', '', $path) ?? $path;
        $path = preg_replace('#^/public#', '', $path) ?? $path;

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . ltrim($path, '/');
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