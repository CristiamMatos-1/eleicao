<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(private string $baseUrl = '')
    {
    }

    public function redirect(string $to): never
    {
        $base = rtrim($this->baseUrl, '/');
        if ($base !== '' && str_starts_with($to, '/')) {
            $to = $base . $to;
        }
        header('Location: ' . $to, true, 302);
        exit;
    }

    public function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}