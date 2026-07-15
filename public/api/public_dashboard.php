<?php

declare(strict_types=1);

use PDO;

header('Content-Type: application/json; charset=utf-8');

$key = $_GET['key'] ?? '';
$key = is_string($key) ? $key : '';

if ($key === '' || strlen($key) > 80) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_key']);
    exit;
}

$services = require __DIR__ . '/../../app/bootstrap.php';
$pdo = $services['pdo'];

$eStmt = $pdo->prepare(
    "SELECT id, type, title, status, expected_voters
     FROM elections
     WHERE public_key = :k
     LIMIT 1"
);
$eStmt->execute([':k' => $key]);
$election = $eStmt->fetch();

if (!$election) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}

$sStmt = $pdo->prepare(
    "SELECT id, number, status, expected_voters, vote_count, opened_at, closed_at
     FROM scrutiniums
     WHERE election_id = :eid
     ORDER BY number DESC
     LIMIT 1"
);
$sStmt->execute([':eid' => $election['id']]);
$scrutiny = $sStmt->fetch();

$result = null;
$live_votes = [];

if ($election['type'] === 'PASTOR') {
    $p = $pdo->prepare(
        "SELECT choice, COUNT(*) AS c
         FROM ballots_pastor
         WHERE election_id = :eid AND scrutiny_id = :sid
         GROUP BY choice"
    );
    $p->execute([':eid' => $election['id'], ':sid' => $scrutiny ? $scrutiny['id'] : 0]);

    $counts = ['SIM' => 0, 'NAO' => 0, 'BRANCO' => 0];
    $total = 0;

    foreach ($p->fetchAll() as $row) {
        $counts[$row['choice']] = (int)$row['c'];
        $total += (int)$row['c'];
    }

    $quorum = intdiv($total, 2) + 1;
    $finalStatus = ($counts['SIM'] >= $quorum) ? 'ELEITO' : 'NAO_ELEITO';

    $result = [
        'final' => $election['status'] === 'CLOSED',
        'type' => 'PASTOR',
        'pastor' => [
            'counts' => $counts,
            'total_votes' => $total,
            'quorum_50p1' => $quorum,
            'final_status' => $finalStatus,
        ],
    ];
} else {
    // Oficiais
    // Busca votos em tempo real da rodada atual
    $stmt = $pdo->prepare(
        "SELECT c.full_name as name, c.status as candidate_status, COUNT(ch.id) as votes 
         FROM candidates c 
         LEFT JOIN ballots_officers b ON b.scrutiny_id = :sid 
         LEFT JOIN ballots_officers_choices ch ON ch.ballot_id = b.id AND ch.candidate_id = c.id
         WHERE c.election_id = :eid 
         GROUP BY c.id, c.full_name, c.status
         ORDER BY votes DESC"
    );
    $stmt->execute([':sid' => $scrutiny ? $scrutiny['id'] : 0, ':eid' => $election['id']]);
    $live_votes = $stmt->fetchAll();

    $wStmt = $pdo->prepare(
        "SELECT COUNT(b.id) 
         FROM ballots_officers b 
         LEFT JOIN ballots_officers_choices ch ON ch.ballot_id = b.id 
         WHERE b.scrutiny_id = :sid AND ch.id IS NULL"
    );
    $wStmt->execute([':sid' => $scrutiny ? $scrutiny['id'] : 0]);
    $whiteVotes = (int)$wStmt->fetchColumn();
    if ($whiteVotes > 0) {
        $live_votes[] = ['name' => 'BRANCOS', 'votes' => $whiteVotes];
    }

    $r = $pdo->prepare(
        "SELECT c.full_name, ec.rule
         FROM elected_candidates ec
         INNER JOIN candidates c ON c.id = ec.candidate_id
         WHERE ec.election_id = :eid
         ORDER BY ec.elected_in_scrutiny ASC, c.full_name ASC"
    );
    $r->execute([':eid' => $election['id']]);

    $result = [
        'final' => $election['status'] === 'CLOSED',
        'type' => 'OFICIAIS',
        'officers' => [
            'elected' => array_map(fn($x) => [
                'full_name' => $x['full_name'],
                'rule' => $x['rule'],
            ], $r->fetchAll()),
            'live_votes' => array_map(fn($x) => [
                'name' => $x['name'],
                'votes' => (int)$x['votes'],
                'candidate_status' => $x['candidate_status'] ?? 'ACTIVE'
            ], $live_votes),
            'quorum_50p1' => intdiv((int)$election['expected_voters'], 2) + 1
        ],
    ];
}

echo json_encode([
    'updated_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
    'election' => [
        'title' => $election['title'],
        'type' => $election['type'],
        'status' => $election['status'],
        'expected_voters' => (int)$election['expected_voters'],
    ],
    'scrutiny' => $scrutiny ? [
        'id' => (int)$scrutiny['id'],
        'number' => (int)$scrutiny['number'],
        'status' => $scrutiny['status'],
    ] : null,
    'progress' => [
        'vote_count' => $scrutiny ? (int)$scrutiny['vote_count'] : 0,
        'expected_voters' => $scrutiny ? (int)$scrutiny['expected_voters'] : (int)$election['expected_voters'],
    ],
    'result' => $result,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);