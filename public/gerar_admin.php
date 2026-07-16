<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$services = require __DIR__ . '/../app/bootstrap.php';
$pdo = $services['pdo'];
$baseUrl = rtrim((string)($services['config']['app']['base_url'] ?? ''), '/');

$email = 'admin@ipccg.org.br';
$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (church_id, role, name, email, password_hash, approved, active)
    VALUES (1, 'SUPER_ADMIN', 'Super Administrador', :email, :hash, 1, 1)
    ON DUPLICATE KEY UPDATE password_hash = :hash2, active = 1, role = 'SUPER_ADMIN'
");

$stmt->execute([
    ':email' => $email,
    ':hash' => $hash,
    ':hash2' => $hash
]);

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Administrador gerado/atualizado com sucesso!</h1>";
echo "<p>O problema do hash foi corrigido diretamente pelo servidor.</p>";
echo "<p>E-mail: <strong>{$email}</strong></p>";
echo "<p>Senha: <strong>{$senha}</strong></p>";
echo "<br><p><a href='" . htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . "/admin/login' style='padding: 10px 15px; background: #4f8cff; color: #fff; text-decoration: none; border-radius: 5px;'>Ir para o Login</a></p>";
echo "<br><br><p style='color:red'><strong>IMPORTANTE:</strong> Por segurança, apague este arquivo (gerar_admin.php) do seu cPanel após conseguir fazer o login!</p>";
echo "</div>";