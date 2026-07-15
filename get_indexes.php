<?php
require_once __DIR__ . '/app/Config/config.php';
$config = require __DIR__ . '/app/Config/config.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$stmt = $pdo->query("SHOW INDEX FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
