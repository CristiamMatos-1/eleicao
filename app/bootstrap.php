<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Csrf;
use App\Core\Auth;
use App\Domain\Services\VoteTransactionService;
use App\Domain\Services\ScrutinyCloseService;

$config = require __DIR__ . '/Config/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

$cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_name($config['app']['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $cookieSecure,
    'httponly' => true,
    'samesite' => 'Strict',
]);
ini_set('session.use_strict_mode', '1');

// Resolve o erro de sessão no cPanel (cria e usa uma pasta local de sessões)
$sessionPath = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
session_save_path($sessionPath);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = (new Database($config['db']['dsn'], $config['db']['user'], $config['db']['pass']))->pdo();

$csrf = new Csrf();
$auth = new Auth($pdo);

$cpfPepper = $config['security']['cpf_pepper'];
if ($cpfPepper === '') {
    throw new RuntimeException('CPF_PEPPER não configurado.');
}

$services = [
    'pdo' => $pdo,
    'csrf' => $csrf,
    'auth' => $auth,
    'vote_tx' => new VoteTransactionService($pdo, $cpfPepper),
    'scrutiny_close' => new ScrutinyCloseService($pdo),
    'config' => $config,
];

return $services;