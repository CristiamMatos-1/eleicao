<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Domain\Services\AttendanceService;
use PDO;
use RuntimeException;
use Throwable;

final class ElectionController extends Controller
{
    public function newForm(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);

        $this->view('admin/election_new.php', [
            'csrf' => $this->services['csrf']->token(),
        ]);
    }

    private function uploadPhoto(string $inputName): ?string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $tmp = $_FILES[$inputName]['tmp_name'];
        $name = $_FILES[$inputName]['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException('Formato de imagem inválido.');
        }
        
        $baseDir = $this->services['config']['app']['base_path'] . '/public/uploads';
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        
        $fileName = uniqid('photo_', true) . '.' . $ext;
        $dest = $baseDir . '/' . $fileName;
        
        if (move_uploaded_file($tmp, $dest)) {
            return 'uploads/' . $fileName;
        }
        return null;
    }

    public function create(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $pdo = $this->services['pdo'];

        $type = strtoupper((string)$this->request->post('type', ''));
        if (!in_array($type, ['PASTOR', 'OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true)) {
            throw new RuntimeException('Tipo inválido.');
        }

        $title = trim((string)$this->request->post('title', ''));
        if ($title === '' || mb_strlen($title) > 190) {
            throw new RuntimeException('Título inválido.');
        }

        $electionDate = (string)$this->request->post('election_date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $electionDate)) {
            throw new RuntimeException('Data inválida.');
        }

        $expectedVoters = (int)$this->request->post('expected_voters', 0);
        if ($expectedVoters <= 0) {
            throw new RuntimeException('Quantidade de eleitores inválida.');
        }

        $vacancies = null;
        if (in_array($type, ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true)) {
            $vac = (int)$this->request->post('vacancies', 0);
            if ($type === 'SOCIEDADES') {
                $vac = 1; // Sociedades sempre tem 1 vaga
            }
            if ($vac <= 0) {
                throw new RuntimeException('Vagas inválidas.');
            }
            $vacancies = $vac;
        }

        $publicKey = $this->uuidV4();
        $cpfSalt = random_bytes(16);

        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        if ($churchId <= 0) {
            throw new RuntimeException('Sessão de Igreja inválida.');
        }

        $pdo->beginTransaction();
        try {
            $insE = $pdo->prepare(
                "INSERT INTO elections (church_id, type, title, election_date, expected_voters, vacancies, status, public_key, cpf_salt, opened_at)
                 VALUES (:cid, :type, :title, :dt, :expected, :vacancies, 'OPEN', :pkey, :salt, NOW())"
            );
            $insE->execute([
                ':cid' => $churchId,
                ':type' => $type,
                ':title' => $title,
                ':dt' => $electionDate,
                ':expected' => $expectedVoters,
                ':vacancies' => $vacancies,
                ':pkey' => $publicKey,
                ':salt' => $cpfSalt,
            ]);

            $electionId = (int)$pdo->lastInsertId();

            $insS = $pdo->prepare(
                "INSERT INTO scrutiniums (election_id, number, status, expected_voters, vote_count)
                 VALUES (:eid, 1, 'OPEN', :expected, 0)"
            );
            $insS->execute([':eid' => $electionId, ':expected' => $expectedVoters]);

            if ($type === 'PASTOR') {
                $fullName = trim((string)$this->request->post('pastor_full_name', ''));
                if ($fullName === '' || mb_strlen($fullName) > 160) {
                    throw new RuntimeException('Nome do candidato inválido.');
                }

                $roleTitle = trim((string)$this->request->post('pastor_role_title', ''));
                if ($roleTitle !== '' && mb_strlen($roleTitle) > 120) {
                    throw new RuntimeException('Função inválida.');
                }

                $term = (int)$this->request->post('pastor_term_years', 0);
                if (!in_array($term, [1, 2, 3, 4, 5], true)) {
                    throw new RuntimeException('Tempo de mandato inválido.');
                }

                $photoPath = $this->uploadPhoto('pastor_photo');

                $insC = $pdo->prepare(
                    "INSERT INTO candidates (election_id, full_name, photo_path, role_title, pastor_term_years, status)
                     VALUES (:eid, :name, :photo, :role_title, :term, 'ACTIVE')"
                );
                $insC->execute([
                    ':eid' => $electionId,
                    ':name' => $fullName,
                    ':photo' => $photoPath,
                    ':role_title' => $roleTitle === '' ? null : $roleTitle,
                    ':term' => $term,
                ]);
            } elseif ($type === 'SOCIEDADES') {
                $uStmt = $pdo->prepare("SELECT name FROM users WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid");
                $uStmt->execute([':cid' => $churchId]);
                $insC = $pdo->prepare(
                    "INSERT INTO candidates (election_id, full_name, photo_path, role_title, pastor_term_years, status)
                     VALUES (:eid, :name, NULL, NULL, NULL, 'ACTIVE')"
                );
                foreach ($uStmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
                    $insC->execute([':eid' => $electionId, ':name' => $user['name']]);
                }
            } else {
                $bulk = (string)$this->request->post('candidates_bulk', '');
                $names = $this->parseCandidatesBulk($bulk);

                if ($names !== []) {
                    $insC = $pdo->prepare(
                        "INSERT INTO candidates (election_id, full_name, photo_path, role_title, pastor_term_years, status)
                         VALUES (:eid, :name, NULL, NULL, NULL, 'ACTIVE')"
                    );
                    foreach ($names as $nm) {
                        $insC->execute([':eid' => $electionId, ':name' => $nm]);
                    }
                }
            }

            $pdo->commit();

            $this->response->redirect('/admin/elections/manage?id=' . $electionId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function manage(): void
    {
        $this->services['auth']->requireRole(['ADMIN', 'CONDUTOR', 'SUPER_ADMIN']);

        $id = (int)$this->request->query('id', 0);
        if ($id <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        $pdo = $this->services['pdo'];

        $eStmt = $pdo->prepare(
            "SELECT id, type, title, election_date, expected_voters, vacancies, status, public_key, cpf_salt
             FROM elections
             WHERE id = :id AND church_id = :cid
             LIMIT 1"
        );
        $eStmt->execute([':id' => $id, ':cid' => $churchId]);
        $election = $eStmt->fetch(PDO::FETCH_ASSOC);
        if (!$election) {
            throw new RuntimeException('Eleição não encontrada.');
        }

        $sStmt = $pdo->prepare(
            "SELECT id, number, status, expected_voters, vote_count, opened_at, closed_at
             FROM scrutiniums
             WHERE election_id = :eid
             ORDER BY number DESC"
        );
        $sStmt->execute([':eid' => $id]);
        $scrutiniums = $sStmt->fetchAll(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare(
            "SELECT id, full_name, status
             FROM candidates
             WHERE election_id = :eid
             ORDER BY full_name ASC"
        );
        $cStmt->execute([':eid' => $id]);
        $candidates = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        // Certifica-se que a coluna votes existe na tabela de eleitos
        try {
            $pdo->exec("ALTER TABLE elected_candidates ADD COLUMN votes INT NOT NULL DEFAULT 0");
        } catch (\Throwable) {}

        $elStmt = $pdo->prepare(
            "SELECT c.full_name, ec.elected_in_scrutiny, ec.rule, ec.votes
             FROM elected_candidates ec
             INNER JOIN candidates c ON c.id = ec.candidate_id
             WHERE ec.election_id = :eid
             ORDER BY ec.elected_in_scrutiny ASC, ec.votes DESC, c.full_name ASC"
        );
        $elStmt->execute([':eid' => $id]);
        $elected = $elStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch vote counts for current scrutiny (if OPEN) or overall (if CLOSED and PASTOR)
        $voteCounts = [];
        $currentScrutinyId = null;
        if (!empty($scrutiniums)) {
            $currentScrutinyId = (int)$scrutiniums[0]['id'];
        }

        $pendingVoters = [];
        if ($currentScrutinyId && $election['status'] === 'OPEN' && $scrutiniums[0]['status'] === 'OPEN') {
            // Se for DIRETORIA, o credenciamento dita quem pode votar
            if ($election['type'] === 'DIRETORIA') {
                $vStmt = $pdo->prepare("SELECT u.id, u.name as full_name, u.cpf FROM users u INNER JOIN election_voters ev ON ev.user_id = u.id WHERE ev.election_id = :eid AND u.role = 'ELEITOR' AND u.approved = 1 AND u.active = 1 ORDER BY u.name ASC");
                $vStmt->execute([':eid' => $id]);
            } else {
                // Pega todos os eleitores aprovados
                $vStmt = $pdo->prepare("SELECT id, name as full_name, cpf FROM users WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid ORDER BY name ASC");
                $vStmt->execute([':cid' => $churchId]);
            }
            $allVoters = $vStmt->fetchAll(PDO::FETCH_ASSOC);

            // Pega todos os hashes de quem já votou neste escrutínio
            $vcStmt = $pdo->prepare("SELECT cpf_hash FROM vote_control WHERE scrutiny_id = :sid");
            $vcStmt->execute([':sid' => $currentScrutinyId]);
            $votedHashes = $vcStmt->fetchAll(PDO::FETCH_COLUMN);
            $votedHashesMap = array_flip($votedHashes);

            foreach ($allVoters as $voter) {
                // IMPORTANT: The hash logic must match VoteTransactionService exactly
                // VoteTransactionService uses: hash_hmac('sha256', $cpfDigits . '|' . bin2hex($saltBinary), $this->cpfPepper)
                $cpfDigits = preg_replace('/\D+/', '', $voter['cpf'] ?? '') ?? '';
                $msg = $cpfDigits . '|' . bin2hex($election['cpf_salt']);
                $cpfPepper = $this->services['config']['security']['cpf_pepper'] ?? '';
                $hash = hash_hmac('sha256', $msg, $cpfPepper);

                if (!isset($votedHashesMap[$hash])) {
                    $pendingVoters[] = $voter['full_name'];
                }
            }
        }

        // Se a eleição tem credenciamento (DIRETORIA)
        $accreditedVoters = [];
        $unaccreditedVoters = [];
        if ($election['type'] === 'DIRETORIA') {
            // Check table existence first to avoid fatal error if admin didn't run SQL yet
            try {
                $pdo->query("SELECT 1 FROM election_voters LIMIT 1");
                $accStmt = $pdo->prepare("
                    SELECT u.id, u.name, u.cpf 
                    FROM users u 
                    LEFT JOIN election_voters ev ON ev.user_id = u.id AND ev.election_id = :eid 
                    WHERE u.role = 'ELEITOR' AND u.approved = 1 AND u.active = 1 
                    ORDER BY u.name ASC
                ");
                $accStmt->execute([':eid' => $id]);
                foreach($accStmt->fetchAll(PDO::FETCH_ASSOC) as $voter) {
                    $check = $pdo->prepare("SELECT 1 FROM election_voters WHERE user_id = :uid AND election_id = :eid");
                    $check->execute([':uid' => $voter['id'], ':eid' => $id]);
                    if ($check->fetchColumn()) {
                        $accreditedVoters[] = $voter;
                    } else {
                        $unaccreditedVoters[] = $voter;
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($currentScrutinyId) {
            if ($election['type'] === 'PASTOR') {
                $stmt = $pdo->prepare("SELECT choice as name, COUNT(*) as votes FROM ballots_pastor WHERE scrutiny_id = :sid GROUP BY choice");
                $stmt->execute([':sid' => $currentScrutinyId]);
                $voteCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT c.full_name as name, COUNT(ch.id) as votes 
                     FROM candidates c 
                     LEFT JOIN ballots_officers b ON b.scrutiny_id = :sid 
                     LEFT JOIN ballots_officers_choices ch ON ch.ballot_id = b.id AND ch.candidate_id = c.id
                     WHERE c.election_id = :eid 
                     GROUP BY c.id, c.full_name 
                     ORDER BY votes DESC"
                );
                $stmt->execute([':sid' => $currentScrutinyId, ':eid' => $id]);
                $voteCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Count white votes (ballots with no choices)
                $wStmt = $pdo->prepare(
                    "SELECT COUNT(b.id) 
                     FROM ballots_officers b 
                     LEFT JOIN ballots_officers_choices ch ON ch.ballot_id = b.id 
                     WHERE b.scrutiny_id = :sid AND ch.id IS NULL"
                );
                $wStmt->execute([':sid' => $currentScrutinyId]);
                $whiteVotes = (int)$wStmt->fetchColumn();
                if ($whiteVotes > 0) {
                    $voteCounts[] = ['name' => 'BRANCOS', 'votes' => $whiteVotes];
                }
            }
        }

        $this->view('admin/election_manage.php', [
            'csrf' => $this->services['csrf']->token(),
            'election' => $election,
            'scrutiniums' => $scrutiniums,
            'candidates' => $candidates,
            'elected' => $elected,
            'voteCounts' => $voteCounts,
            'pendingVoters' => $pendingVoters,
            'accreditedVoters' => $accreditedVoters,
            'unaccreditedVoters' => $unaccreditedVoters,
        ]);
    }

    public function toggleAccreditation(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $electionId = (int)$this->request->post('election_id', 0);
        $userId = (int)$this->request->post('user_id', 0);
        $action = (string)$this->request->post('action', '');
        
        $pdo = $this->services['pdo'];
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO election_voters (election_id, user_id) VALUES (:eid, :uid)");
            $stmt->execute([':eid' => $electionId, ':uid' => $userId]);
            
            // Também adiciona como candidato automaticamente se for DIRETORIA
            $e = $pdo->prepare("SELECT type FROM elections WHERE id = :eid");
            $e->execute([':eid' => $electionId]);
            if ($e->fetchColumn() === 'DIRETORIA') {
                $u = $pdo->prepare("SELECT name FROM users WHERE id = :uid");
                $u->execute([':uid' => $userId]);
                $name = $u->fetchColumn();
                
                $cCheck = $pdo->prepare("SELECT id FROM candidates WHERE election_id = :eid AND full_name = :name");
                $cCheck->execute([':eid' => $electionId, ':name' => $name]);
                if (!$cCheck->fetchColumn()) {
                    $cIns = $pdo->prepare("INSERT INTO candidates (election_id, full_name, status) VALUES (:eid, :name, 'ACTIVE')");
                    $cIns->execute([':eid' => $electionId, ':name' => $name]);
                } else {
                    $cUpd = $pdo->prepare("UPDATE candidates SET status = 'ACTIVE' WHERE election_id = :eid AND full_name = :name");
                    $cUpd->execute([':eid' => $electionId, ':name' => $name]);
                }
            }
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM election_voters WHERE election_id = :eid AND user_id = :uid");
            $stmt->execute([':eid' => $electionId, ':uid' => $userId]);
            
            // Remove candidate se for DIRETORIA
            $e = $pdo->prepare("SELECT type FROM elections WHERE id = :eid");
            $e->execute([':eid' => $electionId]);
            if ($e->fetchColumn() === 'DIRETORIA') {
                $u = $pdo->prepare("SELECT name FROM users WHERE id = :uid");
                $u->execute([':uid' => $userId]);
                $name = $u->fetchColumn();
                $cUpd = $pdo->prepare("UPDATE candidates SET status = 'ELIMINATED' WHERE election_id = :eid AND full_name = :name");
                $cUpd->execute([':eid' => $electionId, ':name' => $name]);
            }
        }
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function attendanceList(): void
    {
        $this->services['auth']->requireRole(['ADMIN','CONDUTOR','SUPER_ADMIN']);
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        $id = (int)$this->request->query('id', 0);
        if ($id <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $pdo = $this->services['pdo'];

        $eStmt = $pdo->prepare("SELECT id, title, type, election_date, status FROM elections WHERE id = :id LIMIT 1");
        $eStmt->execute([':id' => $id]);
        $election = $eStmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            throw new RuntimeException('Eleição não encontrada.');
        }

        /** @var AttendanceService $attSvc */
        $attSvc = $this->services['attendance'] ?? null;
        $voters = [];
        $presentCount = 0;
        if ($attSvc !== null) {
            try {
                $voters = $attSvc->listElectorsWithPresenceFlag($churchId, $id);
                $presentCount = $attSvc->countPresences($id);
            } catch (\Throwable) {
                $voters = [];
            }
        }

        if ($voters === []) {
            if ($election['type'] === 'DIRETORIA') {
                try {
                    $accStmt = $pdo->prepare("
                        SELECT u.name, u.cpf 
                        FROM users u 
                        INNER JOIN election_voters ev ON ev.user_id = u.id AND ev.election_id = :eid 
                        WHERE u.role = 'ELEITOR' AND u.approved = 1 AND u.active = 1 
                        ORDER BY u.name ASC
                    ");
                    $accStmt->execute([':eid' => $id]);
                    $raw = $accStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($raw as $r) {
                        $voters[] = [
                            'name'     => (string)($r['name'] ?? ''),
                            'cpf'      => AttendanceService::normalizeCpf((string)($r['cpf'] ?? '')),
                            'presente' => false,
                            'id'       => 0,
                        ];
                    }
                } catch (\Throwable) {}
            } else {
                $vStmt = $pdo->prepare("SELECT name, cpf FROM users WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid ORDER BY name ASC");
                $vStmt->execute([':cid' => $churchId]);
                $raw = $vStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($raw as $r) {
                    $voters[] = [
                        'name'     => (string)($r['name'] ?? ''),
                        'cpf'      => AttendanceService::normalizeCpf((string)($r['cpf'] ?? '')),
                        'presente' => false,
                        'id'       => 0,
                    ];
                }
            }
        }

        $this->view('admin/attendance_list.php', [
            'election'       => $election,
            'voters'         => $voters,
            'presentCount'   => $presentCount,
            'csrf'           => $this->services['csrf']->token(),
        ]);
    }

    public function registerPresence(): void
    {
        $this->services['auth']->requireRole(['ADMIN','CONDUTOR','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $cpfRaw     = (string)$this->request->post('cpf', '');
        $nome       = trim((string)$this->request->post('nome', ''));
        $userId     = (int)$this->request->post('user_id', 0);

        if ($electionId <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        /** @var AttendanceService $attSvc */
        $attSvc = $this->services['attendance'];
        $attSvc->registerPresence(
            $churchId,
            $electionId,
            $cpfRaw,
            $nome === '' ? null : $nome,
            $userId > 0 ? $userId : null
        );

        $this->response->redirect('/admin/elections/attendance?id=' . $electionId);
    }

    public function setWorkflowStatus(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $newStatus  = (string)$this->request->post('status', '');

        if ($electionId <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $map = [
            'presenca'  => AttendanceService::STATUS_PRESENCA,
            'votacao'   => AttendanceService::STATUS_VOTACAO,
            'encerrada' => AttendanceService::STATUS_ENCERRADA,
        ];

        if (!isset($map[$newStatus])) {
            throw new RuntimeException('Status de fluxo inválido.');
        }

        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        /** @var AttendanceService $attSvc */
        $attSvc = $this->services['attendance'];
        $attSvc->setStatus($churchId, $electionId, $map[$newStatus]);

        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function attendanceListPdf(): void
    {
        $this->services['auth']->requireRole(['ADMIN','CONDUTOR','SUPER_ADMIN']);

        $id = (int)$this->request->query('id', 0);
        if ($id <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        $pdo = $this->services['pdo'];

        $eStmt = $pdo->prepare("SELECT id, title, type, election_date, status FROM elections WHERE id = :id AND church_id = :cid LIMIT 1");
        $eStmt->execute([':id' => $id, ':cid' => $churchId]);
        $election = $eStmt->fetch(PDO::FETCH_ASSOC);
        if (!$election) {
            throw new RuntimeException('Eleição não encontrada.');
        }

        /** @var \App\Domain\Services\AttendancePdfService $pdfSvc */
        $pdfSvc = $this->services['attendance_pdf'];
        $pdfSvc->generate($election, $churchId, $id);
    }

    public function addCandidate(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        if ($electionId <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $pdo = $this->services['pdo'];

        $e = $pdo->prepare("SELECT type FROM elections WHERE id = :id LIMIT 1");
        $e->execute([':id' => $electionId]);
        $type = (string)$e->fetchColumn();
        if ($type !== 'OFICIAIS' && $type !== 'DIRETORIA' && $type !== 'SOCIEDADES') {
            throw new RuntimeException('Esta eleição não aceita múltiplos candidatos.');
        }

        $name = trim((string)$this->request->post('full_name', ''));
        if ($name === '' || mb_strlen($name) > 160) {
            throw new RuntimeException('Nome inválido.');
        }

        $photoPath = $this->uploadPhoto('photo');

        $ins = $pdo->prepare(
            "INSERT INTO candidates (election_id, full_name, photo_path, role_title, pastor_term_years, status)
             VALUES (:eid, :name, :photo, NULL, NULL, 'ACTIVE')"
        );
        $ins->execute([':eid' => $electionId, ':name' => $name, ':photo' => $photoPath]);

        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function deleteCandidate(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $id = (int)$this->request->post('candidate_id', 0);
        $electionId = (int)$this->request->post('election_id', 0);
        
        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = :id AND election_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $electionId]);
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function deleteElection(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $electionId = (int)$this->request->post('election_id', 0);
        $pdo = $this->services['pdo'];
        
        $stmt = $pdo->prepare("DELETE FROM elections WHERE id = :id");
        $stmt->execute([':id' => $electionId]);
        
        $this->response->redirect('/admin');
    }

    public function editCandidate(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $id = (int)$this->request->post('candidate_id', 0);
        $electionId = (int)$this->request->post('election_id', 0);
        $name = trim((string)$this->request->post('full_name', ''));
        
        if ($name === '' || mb_strlen($name) > 160) {
            throw new RuntimeException('Nome inválido.');
        }

        $pdo = $this->services['pdo'];
        
        // Se uma nova foto for enviada, fazemos o upload.
        $photoPath = $this->uploadPhoto('photo');
        
        if ($photoPath) {
            $stmt = $pdo->prepare("UPDATE candidates SET full_name = :name, photo_path = :photo WHERE id = :id AND election_id = :eid");
            $stmt->execute([':name' => $name, ':photo' => $photoPath, ':id' => $id, ':eid' => $electionId]);
        } else {
            $stmt = $pdo->prepare("UPDATE candidates SET full_name = :name WHERE id = :id AND election_id = :eid");
            $stmt->execute([':name' => $name, ':id' => $id, ':eid' => $electionId]);
        }
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function editConfig(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $electionId = (int)$this->request->post('election_id', 0);
        $expectedVoters = (int)$this->request->post('expected_voters', 0);
        
        if ($expectedVoters <= 0) {
            throw new RuntimeException('Quantidade de eleitores inválida.');
        }
        
        $pdo = $this->services['pdo'];
        
        $stmt = $pdo->prepare("UPDATE elections SET expected_voters = :expected WHERE id = :eid");
        $stmt->execute([':expected' => $expectedVoters, ':eid' => $electionId]);
        
        // Também atualizar o expected_voters no escrutínio aberto, para que o fechamento automático funcione com o novo valor
        $stmtScrutiny = $pdo->prepare("UPDATE scrutiniums SET expected_voters = :expected WHERE election_id = :eid AND status = 'OPEN'");
        $stmtScrutiny->execute([':expected' => $expectedVoters, ':eid' => $electionId]);
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function openNextScrutinyForm(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $id = (int)$this->request->query('id', 0);
        if ($id <= 0) {
            throw new RuntimeException('Eleição inválida.');
        }

        $pdo = $this->services['pdo'];

        $eStmt = $pdo->prepare("SELECT id, type, title, status FROM elections WHERE id = :id LIMIT 1");
        $eStmt->execute([':id' => $id]);
        $election = $eStmt->fetch(PDO::FETCH_ASSOC);

        if (!$election || !in_array($election['type'], ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true)) {
            throw new RuntimeException('Apenas eleições de oficiais, diretoria ou sociedades podem ter múltiplos escrutínios.');
        }
        if ($election['status'] !== 'OPEN') {
            throw new RuntimeException('A eleição já está encerrada.');
        }

        // Verifica se não há escrutínio aberto
        $sStmt = $pdo->prepare("SELECT id FROM scrutiniums WHERE election_id = :eid AND status = 'OPEN'");
        $sStmt->execute([':eid' => $id]);
        if ($sStmt->fetchColumn()) {
            throw new RuntimeException('Já existe um escrutínio aberto.');
        }

        // Pega candidatos ativos
        $cStmt = $pdo->prepare("SELECT id, full_name FROM candidates WHERE election_id = :eid AND status = 'ACTIVE' ORDER BY full_name ASC");
        $cStmt->execute([':eid' => $id]);
        $candidates = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/election_next_scrutiny.php', [
            'csrf' => $this->services['csrf']->token(),
            'election' => $election,
            'candidates' => $candidates,
        ]);
    }

    public function openNextScrutiny(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $candidateIds = $_POST['candidate_ids'] ?? [];
        if (!is_array($candidateIds)) {
            $candidateIds = [];
        }

        $pdo = $this->services['pdo'];

        $eStmt = $pdo->prepare("SELECT id, type, status, expected_voters FROM elections WHERE id = :eid LIMIT 1");
        $eStmt->execute([':eid' => $electionId]);
        $election = $eStmt->fetch(PDO::FETCH_ASSOC);

        if (!$election || ($election['type'] !== 'OFICIAIS' && $election['type'] !== 'DIRETORIA' && $election['type'] !== 'SOCIEDADES') || $election['status'] !== 'OPEN') {
            throw new RuntimeException('Ação inválida para esta eleição.');
        }

        // Check again if there's no open scrutiny
        $sStmt = $pdo->prepare("SELECT COUNT(*) FROM scrutiniums WHERE election_id = :eid AND status = 'OPEN'");
        $sStmt->execute([':eid' => $electionId]);
        if ((int)$sStmt->fetchColumn() > 0) {
            throw new RuntimeException('Já existe um escrutínio aberto.');
        }

        $pdo->beginTransaction();
        try {
            // Find current max scrutiny number
            $nStmt = $pdo->prepare("SELECT MAX(number) FROM scrutiniums WHERE election_id = :eid");
            $nStmt->execute([':eid' => $electionId]);
            $maxNumber = (int)$nStmt->fetchColumn();
            $nextNumber = $maxNumber + 1;

            // Set all currently ACTIVE candidates to ELIMINATED first
            $upd1 = $pdo->prepare("UPDATE candidates SET status = 'ELIMINATED' WHERE election_id = :eid AND status = 'ACTIVE'");
            $upd1->execute([':eid' => $electionId]);

            // Then set the selected ones back to ACTIVE
            if (!empty($candidateIds)) {
                $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));
                $upd2 = $pdo->prepare("UPDATE candidates SET status = 'ACTIVE' WHERE election_id = ? AND id IN ($placeholders)");
                $upd2->execute([$electionId, ...$candidateIds]);
            }

            // Create new scrutiny
            $ins = $pdo->prepare(
                "INSERT INTO scrutiniums (election_id, number, status, expected_voters, vote_count)
                 VALUES (:eid, :n, 'OPEN', :expected, 0)"
            );
            $ins->execute([
                ':eid' => $electionId,
                ':n' => $nextNumber,
                ':expected' => $election['expected_voters']
            ]);

            $pdo->commit();

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function closeElection(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $electionId = (int)$this->request->post('election_id', 0);
        $pdo = $this->services['pdo'];
        
        $stmt = $pdo->prepare("UPDATE elections SET status = 'CLOSED', closed_at = NOW() WHERE id = :eid AND status = 'OPEN'");
        $stmt->execute([':eid' => $electionId]);
        
        // Encerrar também qualquer escrutínio que tenha ficado aberto
        $pdo->prepare("UPDATE scrutiniums SET status = 'CLOSED', closed_at = NOW() WHERE election_id = :eid AND status = 'OPEN'")->execute([':eid' => $electionId]);
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    public function resetScrutiny(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $scrutinyId = (int)$this->request->post('scrutiny_id', 0);
        $electionId = (int)$this->request->post('election_id', 0);
        
        $pdo = $this->services['pdo'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM ballots_pastor WHERE scrutiny_id = :sid")->execute([':sid' => $scrutinyId]);
            $pdo->prepare("DELETE FROM ballots_officers WHERE scrutiny_id = :sid")->execute([':sid' => $scrutinyId]);
            $pdo->prepare("DELETE FROM vote_control WHERE scrutiny_id = :sid")->execute([':sid' => $scrutinyId]);
            $pdo->prepare("UPDATE scrutiniums SET vote_count = 0 WHERE id = :sid")->execute([':sid' => $scrutinyId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        
        $this->response->redirect('/admin/elections/manage?id=' . $electionId);
    }

    private function parseCandidatesBulk(string $bulk): array
    {
        $lines = preg_split("/\R/u", $bulk) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $nm = trim($line);
            if ($nm === '') {
                continue;
            }
            if (mb_strlen($nm) > 160) {
                throw new RuntimeException('Um dos nomes excede 160 caracteres.');
            }
            $out[] = $nm;
        }

        $out = array_values(array_unique($out));

        return $out;
    }

    private function uuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        $hex = bin2hex($b);

        return substr($hex, 0, 8) . '-' .
            substr($hex, 8, 4) . '-' .
            substr($hex, 12, 4) . '-' .
            substr($hex, 16, 4) . '-' .
            substr($hex, 20, 12);
    }
}