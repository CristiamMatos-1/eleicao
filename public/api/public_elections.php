<?php

declare(strict_types=1);

use PDO;

header('Content-Type: application/json; charset=utf-8');

$services = require __DIR__ . '/../../app/bootstrap.php';
$pdo = $services['pdo'];

$stmt = $pdo->prepare(
    "SELECT e.public_key, e.title, e.status, e.election_date, c.name as church_name
     FROM elections e
     LEFT JOIN churches c ON c.id = e.church_id
     ORDER BY e.election_date DESC, e.id DESC"
);
$stmt->execute();
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['elections' => $elections], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);