<?php

declare(strict_types=1);

namespace App\Domain\Services;

use PDO;
use RuntimeException;
use Throwable;

final class VoteTransactionService
{
    public function __construct(
        private PDO $pdo,
        private string $cpfPepper
    ) {
    }

    public function castPastorVote(int $electionId, int $scrutinyId, string $cpfDigits, string $choice): array
    {
        $choice = strtoupper($choice);
        if (!in_array($choice, ['SIM', 'NAO', 'BRANCO'], true)) {
            throw new RuntimeException('Voto inválido.');
        }

        return $this->castVoteInternal(
            $electionId,
            $scrutinyId,
            $cpfDigits,
            function (int $electionId, int $scrutinyId, string $ballotToken) use ($choice): void {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO ballots_pastor (election_id, scrutiny_id, ballot_token, choice) VALUES (:election_id, :scrutiny_id, :token, :choice)'
                );
                $stmt->execute([
                    ':election_id' => $electionId,
                    ':scrutiny_id' => $scrutinyId,
                    ':token' => $ballotToken,
                    ':choice' => $choice,
                ]);
            }
        );
    }

    public function castOfficersVote(int $electionId, int $scrutinyId, string $cpfDigits, array $candidateIds): array
    {
        $candidateIds = array_values(array_unique(array_map('intval', $candidateIds)));
        foreach ($candidateIds as $id) {
            if ($id <= 0) {
                throw new RuntimeException('Candidato inválido.');
            }
        }

        return $this->castVoteInternal(
            $electionId,
            $scrutinyId,
            $cpfDigits,
            function (int $electionId, int $scrutinyId, string $ballotToken) use ($candidateIds): void {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO ballots_officers (election_id, scrutiny_id, ballot_token) VALUES (:election_id, :scrutiny_id, :token)'
                );
                $stmt->execute([
                    ':election_id' => $electionId,
                    ':scrutiny_id' => $scrutinyId,
                    ':token' => $ballotToken,
                ]);

                $ballotId = (int)$this->pdo->lastInsertId();

                if ($candidateIds === []) {
                    return;
                }

                $ins = $this->pdo->prepare(
                    'INSERT INTO ballots_officers_choices (ballot_id, candidate_id) VALUES (:ballot_id, :candidate_id)'
                );

                foreach ($candidateIds as $candidateId) {
                    $ins->execute([
                        ':ballot_id' => $ballotId,
                        ':candidate_id' => $candidateId,
                    ]);
                }
            }
        );
    }

    private function castVoteInternal(int $electionId, int $scrutinyId, string $cpfDigits, callable $insertBallot): array
    {
        $cpfDigits = preg_replace('/\D+/', '', $cpfDigits ?? '') ?? '';
        if (!\App\Helpers\CpfValidator::isValid($cpfDigits)) {
            throw new RuntimeException('CPF inválido.');
        }

        $this->pdo->beginTransaction();

        try {
            $scrutiny = $this->lockOpenScrutiny($electionId, $scrutinyId);

            $this->enforcePresenceRequired($electionId, $cpfDigits);

            $cpfHash = $this->computeCpfHash($cpfDigits, $scrutiny['cpf_salt']);

            $ballotToken = bin2hex(random_bytes(16));

            $this->insertVoteControl($scrutinyId, $cpfHash);

            $insertBallot($electionId, $scrutinyId, $ballotToken);

            $newCount = $this->incrementVoteCount($scrutinyId);

            $closedNow = false;
            if ($newCount >= (int)$scrutiny['expected_voters']) {
                $closedNow = $this->closeScrutinyIfOpen($scrutinyId);
            }

            $this->pdo->commit();

            return [
                'vote_count' => $newCount,
                'expected_voters' => (int)$scrutiny['expected_voters'],
                'closed_now' => $closedNow,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function enforcePresenceRequired(int $electionId, string $cpfDigits): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT 1 FROM eleicao_presencas
                 WHERE eleicao_id = :eid AND cpf = :cpf
                 LIMIT 1"
            );
            $stmt->execute([':eid' => $electionId, ':cpf' => $cpfDigits]);
            $has = (bool)$stmt->fetchColumn();
        } catch (Throwable) {
            $has = true;
        }

        if (!$has) {
            throw new RuntimeException('Presença não registrada nesta assembleia.');
        }
    }

    private function lockOpenScrutiny(int $electionId, int $scrutinyId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.status, s.expected_voters, s.vote_count, e.cpf_salt
             FROM scrutiniums s
             INNER JOIN elections e ON e.id = s.election_id
             WHERE s.id = :sid AND s.election_id = :eid
             FOR UPDATE'
        );
        $stmt->execute([':sid' => $scrutinyId, ':eid' => $electionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Escrutínio não encontrado.');
        }
        if ($row['status'] !== 'OPEN') {
            throw new RuntimeException('Escrutínio fechado.');
        }
        if ((int)$row['vote_count'] >= (int)$row['expected_voters']) {
            throw new RuntimeException('Limite de votos atingido.');
        }

        return $row;
    }

    private function computeCpfHash(string $cpfDigits, string $saltBinary): string
    {
        $salt = $saltBinary;
        $msg = $cpfDigits . '|' . bin2hex($salt);
        return hash_hmac('sha256', $msg, $this->cpfPepper);
    }

    private function insertVoteControl(int $scrutinyId, string $cpfHash): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vote_control (scrutiny_id, cpf_hash) VALUES (:sid, :cpf_hash)'
        );

        try {
            $stmt->execute([':sid' => $scrutinyId, ':cpf_hash' => $cpfHash]);
        } catch (Throwable $e) {
            if (($e->getCode() ?? '') === '23000') {
                throw new RuntimeException('CPF já votou neste escrutínio.');
            }
            throw $e;
        }
    }

    private function incrementVoteCount(int $scrutinyId): int
    {
        $upd = $this->pdo->prepare(
            'UPDATE scrutiniums SET vote_count = vote_count + 1 WHERE id = :sid'
        );
        $upd->execute([':sid' => $scrutinyId]);

        $sel = $this->pdo->prepare('SELECT vote_count FROM scrutiniums WHERE id = :sid');
        $sel->execute([':sid' => $scrutinyId]);
        return (int)$sel->fetchColumn();
    }

    private function closeScrutinyIfOpen(int $scrutinyId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE scrutiniums
             SET status = 'CLOSED', closed_at = NOW()
             WHERE id = :sid AND status = 'OPEN'"
        );
        $stmt->execute([':sid' => $scrutinyId]);
        return $stmt->rowCount() === 1;
    }
}