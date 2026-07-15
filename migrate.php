<?php
$services = require __DIR__ . '/app/bootstrap.php';
$pdo = $services['pdo'];

$pdo->exec("ALTER TABLE elections MODIFY COLUMN type ENUM('PASTOR', 'OFICIAIS', 'DIRETORIA', 'SOCIEDADES') NOT NULL;");
echo "Migration done\n";
