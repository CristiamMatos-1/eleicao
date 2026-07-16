<?php

declare(strict_types=1);

$config = [
    'app' => [
        'base_path' => dirname(__DIR__, 2),
        // Deixe vazio para autodetectar (recomendado em cPanel/subpastas).
        // Exemplo manual: '/eleicao'
        'base_url' => '',
        'env' => 'prod',
        'session_name' => 'ELECTSESSID',
    ],
    'db' => [
        // Substitua pelo nome do seu banco de dados
        'dsn' => 'mysql:host=localhost;dbname=SEU_BANCO_DE_DADOS;charset=utf8mb4',
        // Substitua pelo usuário do banco
        'user' => 'SEU_USUARIO_AQUI',
        // Substitua pela senha do banco
        'pass' => 'SUA_SENHA_AQUI',
    ],
    'security' => [
        // Crie uma senha/hash bem grande e secreta aqui e NUNCA mude depois que a eleição começar
        'cpf_pepper' => 'COLOQUE_UMA_STRING_SECRETA_GRANDE_AQUI_ASDKJHASDKJHASD',
    ],
];

return $config;