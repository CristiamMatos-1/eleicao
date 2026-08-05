<?php

declare(strict_types=1);

// =========================================================
// ATENÇÃO: Copie este arquivo para config.php e preencha
//          os valores reais. NUNCA commit config.php!
// =========================================================

$config = [
    'app' => [
        'base_path' => dirname(__DIR__, 2),
        // Deixe '' para autodetectar subpasta (recomendado no cPanel).
        // Exemplo manual: '/eleicao'
        'base_url'  => '',
        'env'       => 'prod',
        'session_name' => 'ELECTSESSID',
    ],
    'db' => [
        // Substitua pelos dados do banco criado no cPanel
        'dsn'  => 'mysql:host=localhost;dbname=SEU_BANCO;charset=utf8mb4',
        'user' => 'SEU_USUARIO',
        'pass' => 'SUA_SENHA',
    ],
    'security' => [
        // String longa, aleatória e FIXA. Nunca altere após a primeira eleição!
        'cpf_pepper' => 'COLOQUE_UMA_STRING_SECRETA_GRANDE_E_ALEATORIA_AQUI',
    ],
];

return $config;
