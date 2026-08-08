<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Csrf;
use App\Core\Auth;
use App\Domain\Services\VoteTransactionService;
use App\Domain\Services\ScrutinyCloseService;
use App\Domain\Services\AttendanceService;
use App\Domain\Services\AttendancePdfService;

$config = require __DIR__ . '/Config/config.php';

$applyIniOverride = static function (array $config): array {
    $candidates = [
        dirname(__DIR__, 2) . '/eleicao.ini',
        __DIR__ . '/Config/eleicao.ini',
    ];
    foreach ($candidates as $iniFile) {
        if (!is_file($iniFile) || !is_readable($iniFile)) {
            continue;
        }
        $ini = parse_ini_file($iniFile, true);
        if ($ini === false) {
            continue;
        }
        if (isset($ini['db']['dsn']))   $config['db']['dsn']   = (string)$ini['db']['dsn'];
        if (isset($ini['db']['user']))  $config['db']['user']  = (string)$ini['db']['user'];
        if (isset($ini['db']['pass']))  $config['db']['pass']  = (string)$ini['db']['pass'];
        if (isset($ini['security']['cpf_pepper'])) $config['security']['cpf_pepper'] = (string)$ini['security']['cpf_pepper'];
        if (isset($ini['app']['base_url'])) $config['app']['base_url'] = (string)$ini['app']['base_url'];
        if (isset($ini['app']['env']))      $config['app']['env']      = (string)$ini['app']['env'];
    }
    return $config;
};
$config = $applyIniOverride($config);

$resolveBaseUrl = static function (array $config): string {
    $configured = trim((string)($config['app']['base_url'] ?? ''));
    if ($configured !== '' && $configured !== '/') {
        $configured = '/' . trim($configured, '/');
    } else {
        $configured = '';
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }
    if ($scriptDir !== '' && str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }

    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $isWeb = $requestUri !== '' || $scriptName !== '';

    if (!$isWeb) {
        return $configured;
    }

    if ($configured !== '') {
        if ($requestUri === '' || str_starts_with($requestUri, $configured . '/') || $requestUri === $configured) {
            return $configured;
        }
    }

    return $scriptDir;
};

$config['app']['base_url'] = $resolveBaseUrl($config);

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
    'attendance' => new AttendanceService($pdo),
    'attendance_pdf' => new AttendancePdfService($pdo),
    'config' => $config,
];

return $services;