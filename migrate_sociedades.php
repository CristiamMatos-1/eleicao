<?php
require_once __DIR__ . '/app/Config/config.php';
$config = require __DIR__ . '/app/Config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("ALTER TABLE elections MODIFY COLUMN type ENUM('PASTOR', 'OFICIAIS', 'DIRETORIA', 'SOCIEDADES') NOT NULL");
    echo "Tabela elections alterada com sucesso. Tipo SOCIEDADES adicionado.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
