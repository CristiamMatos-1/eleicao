<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Auth
{
    private const SESS_UID = '__uid';
    public const SESS_ROLE = '__role';
    public const SESS_CHURCH_ID = 'church_id';

    public function __construct(private PDO $pdo)
    {
    }

    public function userId(): ?int
    {
        $v = $_SESSION[self::SESS_UID] ?? null;
        return is_int($v) ? $v : null;
    }

    public function role(): ?string
    {
        $r = $_SESSION[self::SESS_ROLE] ?? null;
        return is_string($r) ? $r : null;
    }

    public function loginElectorByCpf(string $cpfDigits): void
    {
        $cpfDigits = preg_replace('/\D+/', '', $cpfDigits ?? '') ?? '';
        if (strlen($cpfDigits) !== 11) {
            throw new RuntimeException('CPF inválido.');
        }

        // Tenta buscar o eleitor com base apenas no CPF.
        // Se houver mais de um (o mesmo CPF em igrejas diferentes), prioriza aquele
        // cuja igreja tem uma eleição 'OPEN'.
        $query = "
            SELECT u.id, u.role, u.approved, u.active, u.church_id
            FROM users u
            LEFT JOIN elections e ON e.church_id = u.church_id AND e.status = 'OPEN'
            WHERE u.cpf = :cpf AND u.role = 'ELEITOR'
            ORDER BY (e.id IS NOT NULL) DESC, u.id ASC
            LIMIT 1
        ";
        $params = [':cpf' => $cpfDigits];

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['active'] !== 1) {
            throw new RuntimeException('Acesso negado ou usuário inativo.');
        }
        if ((int)$row['approved'] !== 1) {
            throw new RuntimeException('Cadastro ainda não aprovado.');
        }

        session_regenerate_id(true);
        $_SESSION[self::SESS_UID] = (int)$row['id'];
        $_SESSION[self::SESS_ROLE] = (string)$row['role'];
        $_SESSION[self::SESS_CHURCH_ID] = (int)$row['church_id'];
    }

    public function loginAdminByEmailPassword(string $email, string $password): void
    {
        $email = trim($email);
        if ($email === '' || $password === '') {
            throw new RuntimeException('Credenciais inválidas.');
        }

        // O e-mail deve ser único na tabela (índice idx_email foi criado), então não precisamos do church_id.
        $stmt = $this->pdo->prepare(
            "SELECT id, role, password_hash, active, church_id
             FROM users
             WHERE email = :email AND role IN ('ADMIN', 'CONDUTOR', 'SUPER_ADMIN')
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['active'] !== 1) {
            throw new RuntimeException('Credenciais inválidas ou usuário inativo.');
        }

        $hash = (string)($row['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            throw new RuntimeException('Credenciais inválidas.');
        }

        session_regenerate_id(true);
        $_SESSION[self::SESS_UID] = (int)$row['id'];
        $_SESSION[self::SESS_ROLE] = (string)$row['role'];
        $_SESSION[self::SESS_CHURCH_ID] = (int)$row['church_id'];
    }

    public function requireRole(array $roles): void
    {
        $role = $this->role();
        if ($role === 'SUPER_ADMIN') {
            return; // SUPER_ADMIN can do anything
        }
        if (!$role || !in_array($role, $roles, true)) {
            throw new RuntimeException('Não autorizado.');
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}