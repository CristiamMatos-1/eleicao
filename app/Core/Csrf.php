<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    private const KEY = '__csrf';

    public function token(): string
    {
        if (!isset($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY]) || $_SESSION[self::KEY] === '') {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public function validate(?string $token): void
    {
        $sess = $_SESSION[self::KEY] ?? null;
        if (!is_string($sess) || $sess === '' || !is_string($token) || $token === '' || !hash_equals($sess, $token)) {
            throw new RuntimeException('CSRF inválido.');
        }
    }
}