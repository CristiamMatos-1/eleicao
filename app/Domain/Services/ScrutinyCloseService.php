<?php

declare(strict_types=1);

namespace App\Domain\Services;

use PDO;
use RuntimeException;
use Throwable;

final class ScrutinyCloseService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function closeAndCompute(int $electionId, int $scrutinyId): array
    {
        $this->pdo->beginTransaction();

        try {
            $ctx = $this->lockContext($electionId, $scrutinyId);

            if ($ctx['scrutiny_status'] !== 'OPEN') {
                throw new RuntimeException('Escrutínio já está fechado.');
            }

            $this->closeScrutiny($scrutinyId);

            $result = match ($ctx['election_type']) {
                'PASTOR' => $this->computePastorResult($electionId, $scrutinyId),
                'OFICIAIS' => $this->computeOfficersResultAndAdvance($electionId, (int)$ctx['scrutiny_number'], $scrutinyId),
                'DIRETORIA' => $this->computeOfficersResultAndAdvance($electionId, (int)$ctx['scrutiny_number'], $scrutinyId),
                'SOCIEDADES' => $this->computeOfficersResultAndAdvance($electionId, (int)$ctx['scrutiny_number'], $scrutinyId),
                default => throw new RuntimeException('Tipo de eleição inválido.'),
            };

            $this->maybeCloseElection($electionId);

            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function lockContext(int $electionId, int $scrutinyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.id AS election_id, e.type AS election_type, e.expected_voters, e.vacancies, e.status AS election_status,
                    s.id AS scrutiny_id, s.number AS scrutiny_number, s.status AS scrutiny_status
             FROM elections e
             INNER JOIN scrutiniums s ON s.election_id = e.id
             WHERE e.id = :eid AND s.id = :sid
             FOR UPDATE"
        );
        $stmt->execute([':eid' => $electionId, ':sid' => $scrutinyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Contexto não encontrado.');
        }

        return $row;
    }

    private function closeScrutiny(int $scrutinyId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE scrutiniums SET status = 'CLOSED', closed_at = NOW() WHERE id = :sid AND status = 'OPEN'"
        );
        $stmt->execute([':sid' => $scrutinyId]);
    }

    private function computePastorResult(int $electionId, int $scrutinyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT choice, COUNT(*) AS c
             FROM ballots_pastor
             WHERE election_id = :eid AND scrutiny_id = :sid
             GROUP BY choice"
        );
        $stmt->execute([':eid' => $electionId, ':sid' => $scrutinyId]);

        $counts = ['SIM' => 0, 'NAO' => 0, 'BRANCO' => 0];
        $total = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$row['choice']] = (int)$row['c'];
            $total += (int)$row['c'];
        }

        $quorum = intdiv($total, 2) + 1;
        $elected = $counts['SIM'] >= $quorum;

        return [
            'type' => 'PASTOR',
            'total_votes' => $total,
            'quorum_50p1' => $quorum,
            'counts' => $counts,
            'final_status' => $elected ? 'ELEITO' : 'NAO_ELEITO',
        ];
    }

    private function computeOfficersResultAndAdvance(int $electionId, int $scrutinyNumber, int $scrutinyId): array
    {
        $expected = $this->getExpectedVotersFromScrutiny($scrutinyId);
        $vacancies = $this->getVacanciesFromElection($electionId);

        $quorum = intdiv($expected, 2) + 1;

        $alreadyElected = $this->countAlreadyElected($electionId);
        $remainingVacancies = max(0, $vacancies - $alreadyElected);

        $tallies = $this->getOfficerTallies($electionId, $scrutinyId);

        if ($remainingVacancies === 0) {
            return [
                'type' => 'OFICIAIS',
                'scrutiny_number' => $scrutinyNumber,
                'quorum_50p1' => $quorum,
                'elected_now' => [],
                'remaining_vacancies' => 0,
                'advanced_to_next_scrutiny' => false,
            ];
        }

        if ($scrutinyNumber <= 3) {
            $eligible = [];
            foreach ($tallies as $row) {
                if ($row['votes'] >= $quorum) {
                    $eligible[] = $row;
                }
            }

            usort($eligible, fn(array $a, array $b) => $b['votes'] <=> $a['votes']);

            $electedNow = array_slice($eligible, 0, $remainingVacancies);
            $this->markElected($electionId, $scrutinyNumber, $electedNow, 'QUORUM_50P1');

            $remainingVacancies = max(0, $remainingVacancies - count($electedNow));

            if ($remainingVacancies > 0) {
                $this->eliminateElectedFromActive($electionId);
                // NOT creating the next scrutiny automatically anymore.
                // $this->createNextScrutinyIfNeeded($electionId, $scrutinyNumber + 1);
                return [
                    'type' => 'OFICIAIS',
                    'scrutiny_number' => $scrutinyNumber,
                    'quorum_50p1' => $quorum,
                    'elected_now' => $electedNow,
                    'remaining_vacancies' => $remainingVacancies,
                    'advanced_to_next_scrutiny' => false,
                ];
            }

            return [
                'type' => 'OFICIAIS',
                'scrutiny_number' => $scrutinyNumber,
                'quorum_50p1' => $quorum,
                'elected_now' => $electedNow,
                'remaining_vacancies' => 0,
                'advanced_to_next_scrutiny' => false,
            ];
        }

        $winners = array_slice($tallies, 0, $remainingVacancies);
        $this->markElected($electionId, $scrutinyNumber, $winners, 'MAIORIA_SIMPLES');
        $this->eliminateElectedFromActive($electionId);

        return [
            'type' => 'OFICIAIS',
            'scrutiny_number' => $scrutinyNumber,
            'quorum_50p1' => $quorum,
            'elected_now' => $winners,
            'remaining_vacancies' => max(0, $remainingVacancies - count($winners)),
            'advanced_to_next_scrutiny' => false,
        ];
    }

    private function getOfficerTallies(int $electionId, int $scrutinyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id AS candidate_id, c.full_name,
                    COUNT(ch.id) AS votes
             FROM candidates c
             LEFT JOIN ballots_officers b ON b.scrutiny_id = :sid
             LEFT JOIN ballots_officers_choices ch ON ch.ballot_id = b.id AND ch.candidate_id = c.id
             WHERE c.election_id = :eid AND c.status = 'ACTIVE'
             GROUP BY c.id, c.full_name
             ORDER BY votes DESC, c.full_name ASC"
        );
        $stmt->execute([':eid' => $electionId, ':sid' => $scrutinyId]);

        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'candidate_id' => (int)$r['candidate_id'],
                'full_name' => $r['full_name'],
                'votes' => (int)$r['votes'],
            ];
        }

        return $rows;
    }

    private function markElected(int $electionId, int $scrutinyNumber, array $electedRows, string $rule): void
    {
        if ($electedRows === []) {
            return;
        }

        // Make sure the votes column exists in elected_candidates table
        try {
            $this->pdo->exec("ALTER TABLE elected_candidates ADD COLUMN votes INT NOT NULL DEFAULT 0");
        } catch (\Throwable) {
            // Column probably already exists
        }

        $ins = $this->pdo->prepare(
            "INSERT INTO elected_candidates (election_id, candidate_id, elected_in_scrutiny, rule, votes)
             VALUES (:eid, :cid, :sn, :rule, :votes)"
        );

        $upd = $this->pdo->prepare(
            "UPDATE candidates SET status = 'ELECTED' WHERE id = :cid AND election_id = :eid"
        );

        foreach ($electedRows as $row) {
            $cid = (int)$row['candidate_id'];
            $votes = (int)$row['votes'];
            $ins->execute([':eid' => $electionId, ':cid' => $cid, ':sn' => $scrutinyNumber, ':rule' => $rule, ':votes' => $votes]);
            $upd->execute([':cid' => $cid, ':eid' => $electionId]);
        }
    }

    private function eliminateElectedFromActive(int $electionId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE candidates SET status = 'ELIMINATED'
             WHERE election_id = :eid AND status = 'ACTIVE' AND id IN (
               SELECT candidate_id FROM elected_candidates WHERE election_id = :eid2
             )"
        );
        $stmt->execute([':eid' => $electionId, ':eid2' => $electionId]);
    }

    private function createNextScrutinyIfNeeded(int $electionId, int $nextNumber): void
    {
        $sel = $this->pdo->prepare(
            "SELECT COUNT(*) FROM scrutiniums WHERE election_id = :eid AND number = :n"
        );
        $sel->execute([':eid' => $electionId, ':n' => $nextNumber]);
        if ((int)$sel->fetchColumn() > 0) {
            return;
        }

        $expected = $this->getExpectedVotersFromElection($electionId);

        $ins = $this->pdo->prepare(
            "INSERT INTO scrutiniums (election_id, number, status, expected_voters, vote_count)
             VALUES (:eid, :n, 'OPEN', :expected, 0)"
        );
        $ins->execute([':eid' => $electionId, ':n' => $nextNumber, ':expected' => $expected]);
    }

    private function countAlreadyElected(int $electionId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM elected_candidates WHERE election_id = :eid");
        $stmt->execute([':eid' => $electionId]);
        return (int)$stmt->fetchColumn();
    }

    private function getVacanciesFromElection(int $electionId): int
    {
        $stmt = $this->pdo->prepare("SELECT vacancies FROM elections WHERE id = :eid");
        $stmt->execute([':eid' => $electionId]);
        $v = $stmt->fetchColumn();
        return (int)($v ?? 0);
    }

    private function getExpectedVotersFromScrutiny(int $scrutinyId): int
    {
        $stmt = $this->pdo->prepare("SELECT expected_voters FROM scrutiniums WHERE id = :sid");
        $stmt->execute([':sid' => $scrutinyId]);
        return (int)$stmt->fetchColumn();
    }

    private function getExpectedVotersFromElection(int $electionId): int
    {
        $stmt = $this->pdo->prepare("SELECT expected_voters FROM elections WHERE id = :eid");
        $stmt->execute([':eid' => $electionId]);
        return (int)$stmt->fetchColumn();
    }

    private function maybeCloseElection(int $electionId): void
    {
        $e = $this->pdo->prepare("SELECT type, vacancies FROM elections WHERE id = :eid FOR UPDATE");
        $e->execute([':eid' => $electionId]);
        $row = $e->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        if (in_array($row['type'], ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true)) {
            $vacancies = (int)($row['vacancies'] ?? 0);
            if ($vacancies <= 0) {
                return;
            }
            $elected = $this->countAlreadyElected($electionId);
            if ($elected >= $vacancies) {
                $this->closeElection($electionId);
            }
            return;
        }

        $open = $this->pdo->prepare("SELECT COUNT(*) FROM scrutiniums WHERE election_id = :eid AND status = 'OPEN'");
        $open->execute([':eid' => $electionId]);
        if ((int)$open->fetchColumn() === 0) {
            $this->closeElection($electionId);
        }
    }

    private function closeElection(int $electionId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE elections SET status = 'CLOSED', closed_at = NOW() WHERE id = :eid AND status <> 'CLOSED'"
        );
        $stmt->execute([':eid' => $electionId]);
    }
}