<?php

declare(strict_types=1);

namespace App\Domain\Services;

use PDO;
use RuntimeException;
use Throwable;

final class AttendanceService
{
    public const STATUS_PRESENCA = 'aberta_para_presenca';
    public const STATUS_VOTACAO  = 'aberta_para_votacao';
    public const STATUS_ENCERRADA = 'encerrada';

    public function __construct(
        private PDO $pdo
    ) {
    }

    public static function normalizeCpf(string $cpf): string
    {
        return (string)preg_replace('/\D+/', '', $cpf ?? '');
    }

    public function electionMustAllowAttendance(int $electionId, int $churchId): void
    {
        $s = $this->pdo->prepare(
            "SELECT status FROM elections WHERE id = :eid AND church_id = :cid LIMIT 1"
        );
        $s->execute([':eid' => $electionId, ':cid' => $churchId]);
        $status = (string)$s->fetchColumn();

        if ($status !== self::STATUS_PRESENCA && $status !== 'OPEN') {
            throw new RuntimeException('Esta assembleia não está aberta para registro de presença.');
        }
    }

    public function electionMustAllowVoting(int $electionId, int $churchId): void
    {
        $s = $this->pdo->prepare(
            "SELECT status FROM elections WHERE id = :eid AND church_id = :cid LIMIT 1"
        );
        $s->execute([':eid' => $electionId, ':cid' => $churchId]);
        $status = (string)$s->fetchColumn();

        $allowed = [self::STATUS_VOTACAO, 'OPEN'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Esta assembleia não está aberta para votação.');
        }
    }

    public function registerPresence(
        int $churchId,
        int $electionId,
        string $cpfRaw,
        ?string $nome = null,
        ?int $userId = null
    ): array {
        $cpfDigits = self::normalizeCpf($cpfRaw);
        if (!\App\Helpers\CpfValidator::isValid($cpfDigits)) {
            throw new RuntimeException('CPF inválido.');
        }

        $this->electionMustAllowAttendance($electionId, $churchId);

        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $lockStmt = $pdo->prepare(
                "SELECT id, church_id FROM elections WHERE id = :eid FOR UPDATE"
            );
            $lockStmt->execute([':eid' => $electionId]);
            $row = $lockStmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || (int)$row['church_id'] !== $churchId) {
                throw new RuntimeException('Eleição não encontrada.');
            }

            if ($nome === null || trim($nome) === '') {
                if ($userId !== null && $userId > 0) {
                    $u = $pdo->prepare("SELECT name FROM users WHERE id = :uid AND church_id = :cid LIMIT 1");
                    $u->execute([':uid' => $userId, ':cid' => $churchId]);
                    $nome = (string)$u->fetchColumn();
                }
                if ($nome === '' || $nome === false || $nome === null) {
                    $u = $pdo->prepare("SELECT name FROM users WHERE cpf = :cpf AND church_id = :cid LIMIT 1");
                    $u->execute([':cpf' => $cpfDigits, ':cid' => $churchId]);
                    $found = $u->fetchColumn();
                    $nome = is_string($found) && $found !== '' ? $found : 'Eleitor';
                }
            }
            $nome = trim((string)$nome);
            if ($nome === '') {
                $nome = 'Eleitor';
            }

            $resolveUserId = $userId;
            if ($resolveUserId === null || $resolveUserId <= 0) {
                $u = $pdo->prepare("SELECT id FROM users WHERE cpf = :cpf AND church_id = :cid LIMIT 1");
                $u->execute([':cpf' => $cpfDigits, ':cid' => $churchId]);
                $foundId = $u->fetchColumn();
                if (is_numeric($foundId) && (int)$foundId > 0) {
                    $resolveUserId = (int)$foundId;
                }
            }

            try {
                $ins = $pdo->prepare(
                    "INSERT INTO eleicao_presencas (church_id, eleicao_id, usuario_id, cpf, nome, data_registro)
                     VALUES (:cid, :eid, :uid, :cpf, :nome, NOW())"
                );
                $ins->execute([
                    ':cid'  => $churchId,
                    ':eid'  => $electionId,
                    ':uid'  => ($resolveUserId > 0 ? $resolveUserId : null),
                    ':cpf'  => $cpfDigits,
                    ':nome' => $nome,
                ]);
            } catch (Throwable $e) {
                if (($e->getCode() ?? '') === '23000') {
                    throw new RuntimeException('Presença já registrada para este CPF nesta assembleia.');
                }
                throw $e;
            }

            $pdo->commit();

            return [
                'cpf'  => $cpfDigits,
                'nome' => $nome,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function hasPresence(int $electionId, string $cpfRaw): bool
    {
        $cpfDigits = self::normalizeCpf($cpfRaw);
        if ($cpfDigits === '' || strlen($cpfDigits) !== 11) {
            return false;
        }
        $s = $this->pdo->prepare(
            "SELECT 1 FROM eleicao_presencas WHERE eleicao_id = :eid AND cpf = :cpf LIMIT 1"
        );
        $s->execute([':eid' => $electionId, ':cpf' => $cpfDigits]);
        return (bool)$s->fetchColumn();
    }

    public function listPresences(int $churchId, int $electionId): array
    {
        $s = $this->pdo->prepare(
            "SELECT p.id, p.cpf, p.nome, p.data_registro, p.usuario_id
             FROM eleicao_presencas p
             WHERE p.church_id = :cid AND p.eleicao_id = :eid
             ORDER BY p.nome ASC"
        );
        $s->execute([':cid' => $churchId, ':eid' => $electionId]);
        return $s->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listElectorsWithPresenceFlag(int $churchId, int $electionId): array
    {
        $e = $this->pdo->prepare("SELECT type FROM elections WHERE id = :eid AND church_id = :cid LIMIT 1");
        $e->execute([':eid' => $electionId, ':cid' => $churchId]);
        $type = (string)$e->fetchColumn();

        if ($type === 'DIRETORIA') {
            $base = $this->pdo->prepare("
                SELECT u.id, u.name, u.cpf
                FROM users u
                INNER JOIN election_voters ev ON ev.user_id = u.id AND ev.election_id = :eid
                WHERE u.role = 'ELEITOR' AND u.approved = 1 AND u.active = 1
                ORDER BY u.name ASC
            ");
            $base->execute([':eid' => $electionId]);
        } else {
            $base = $this->pdo->prepare("
                SELECT id, name, cpf
                FROM users
                WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid
                ORDER BY name ASC
            ");
            $base->execute([':cid' => $churchId]);
        }
        $electors = $base->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $presenca = $this->pdo->prepare("SELECT cpf FROM eleicao_presencas WHERE eleicao_id = :eid");
        $presenca->execute([':eid' => $electionId]);
        $map = array_flip($presenca->fetchAll(PDO::FETCH_COLUMN) ?: []);

        $out = [];
        foreach ($electors as $e) {
            $cpf = self::normalizeCpf((string)($e['cpf'] ?? ''));
            $out[] = [
                'id'        => (int)($e['id'] ?? 0),
                'name'      => (string)($e['name'] ?? ''),
                'cpf'       => $cpf,
                'presente'  => isset($map[$cpf]),
            ];
        }
        return $out;
    }

    public function countPresences(int $electionId): int
    {
        $s = $this->pdo->prepare("SELECT COUNT(*) FROM eleicao_presencas WHERE eleicao_id = :eid");
        $s->execute([':eid' => $electionId]);
        return (int)$s->fetchColumn();
    }

    public function setStatus(int $churchId, int $electionId, string $newStatus): void
    {
        $allowed = [self::STATUS_PRESENCA, self::STATUS_VOTACAO, self::STATUS_ENCERRADA, 'OPEN', 'CLOSED'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new RuntimeException('Status inválido.');
        }

        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare(
                "UPDATE elections SET status = :status WHERE id = :eid AND church_id = :cid"
            );
            $upd->execute([':status' => $newStatus, ':eid' => $electionId, ':cid' => $churchId]);

            if ($newStatus === self::STATUS_ENCERRADA || $newStatus === 'CLOSED') {
                $pdo->prepare(
                    "UPDATE scrutiniums SET status = 'CLOSED', closed_at = NOW()
                     WHERE election_id = :eid AND status = 'OPEN'"
                )->execute([':eid' => $electionId]);
            }

            if ($newStatus === self::STATUS_PRESENCA) {
                try {
                    $pdo->exec(
                        "INSERT INTO registration_settings (id, registration_open)
                         VALUES (1, 1)
                         ON DUPLICATE KEY UPDATE registration_open = 1"
                    );
                } catch (\Throwable) {
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
