<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(
        protected Request $request,
        protected Response $response,
        protected array $services
    ) {
    }

    protected function view(string $file, array $data = []): void
    {
        $base = $this->services['config']['app']['base_path'] . '/app/Views/';
        $path = $base . ltrim($file, '/');
        if (!is_file($path)) {
            http_response_code(500);
            echo 'View não encontrada.';
            exit;
        }

        extract($data, EXTR_SKIP);
        require $path;
    }
}