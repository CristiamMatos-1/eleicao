<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class App
{
    private array $routes = [];

    public function __construct(private array $services)
    {
    }

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function run(): void
    {
        $baseUrl = (string)($this->services['config']['app']['base_url'] ?? '');
        $req = new Request($baseUrl);
        $res = new Response($baseUrl);

        $method = $req->method();
        $path = $req->path();

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            $errorMessage = "404 - Página não encontrada.\nA rota '$path' não existe no sistema.";
            $base = $this->services['config']['app']['base_path'] . '/app/Views/';
            $errorPath = $base . 'error.php';
            
            if (is_file($errorPath)) {
                require $errorPath;
            } else {
                echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
            }
            return;
        }

        try {
            $handler($req, $res, $this->services);
        } catch (RuntimeException $e) {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'application/json')) {
                $res->json(['error' => $e->getMessage()], 400);
                return;
            }
            http_response_code(400);
            
            // Renderiza a view de erro customizada
            $errorMessage = $e->getMessage();
            $base = $this->services['config']['app']['base_path'] . '/app/Views/';
            $path = $base . 'error.php';
            
            if (is_file($path)) {
                require $path;
            } else {
                echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
            }
        }
    }
}