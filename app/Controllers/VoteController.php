<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Domain\Services\AttendanceService;
use PDO;
use RuntimeException;

final class VoteController extends Controller
{
    public function showBallot(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);

        $pdo = $this->services['pdo'];
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $e = $pdo->prepare(
            "SELECT e.id, e.type, e.assembly_type, e.title, e.election_date, e.status, e.vacancies,
                    c.name AS church_name, c.legal_name AS church_legal_name
             FROM elections e
             INNER JOIN churches c ON c.id = e.church_id
             WHERE e.status IN ('OPEN','aberta_para_presenca','aberta_para_votacao')
               AND e.church_id = :cid
             ORDER BY e.id DESC
             LIMIT 1"
        );
        $e->execute([':cid' => $churchId]);
        $e = $e->fetch(PDO::FETCH_ASSOC);

        if (!$e) {
            throw new RuntimeException('Não há eleição aberta.');
        }
        if (!isset($e['assembly_type']) || $e['assembly_type'] === null || $e['assembly_type'] === '') {
            $e['assembly_type'] = 'ORDINARIA';
        }
        $entityName = (string)($e['church_legal_name'] ?? $e['church_name'] ?? '');
        if ($entityName === '') {
            $cStmt = $pdo->prepare("SELECT name, legal_name FROM churches WHERE id = :cid LIMIT 1");
            $cStmt->execute([':cid' => $churchId]);
            $ch = $cStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $entityName = (string)(($ch['legal_name'] ?? '') !== '' ? $ch['legal_name'] : ($ch['name'] ?? ''));
        }
        $e['entity_name'] = $entityName;

        $workflowLocked = !in_array($e['status'], ['OPEN', AttendanceService::STATUS_VOTACAO], true);

        $s = $pdo->prepare("SELECT id, number, status FROM scrutiniums WHERE election_id = :eid AND status = 'OPEN' ORDER BY number DESC LIMIT 1");
        $s->execute([':eid' => $e['id']]);
        $scrutiny = $s->fetch(PDO::FETCH_ASSOC);

        if (!$scrutiny && !$workflowLocked) {
            throw new RuntimeException('Não há escrutínio aberto.');
        }

        $userId = (int)$this->services['auth']->userId();
        $u = $pdo->prepare("SELECT name, cpf FROM users WHERE id = :id LIMIT 1");
        $u->execute([':id' => $userId]);
        $me = $u->fetch(PDO::FETCH_ASSOC) ?: [];
        $myCpf = AttendanceService::normalizeCpf((string)($me['cpf'] ?? ''));
        $myName = (string)($me['name'] ?? '');

        /** @var AttendanceService $attSvc */
        $attSvc = $this->services['attendance'] ?? null;
        $presenceRegistered = false;
        if ($attSvc !== null) {
            try {
                $presenceRegistered = $attSvc->hasPresence((int)$e['id'], $myCpf);
            } catch (\Throwable) {
                $presenceRegistered = false;
            }
        }

        $candidates = [];
        if (in_array($e['type'], ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true)) {
            $c = $pdo->prepare("SELECT id, full_name, photo_path FROM candidates WHERE election_id = :eid AND status = 'ACTIVE' ORDER BY full_name ASC");
            $c->execute([':eid' => $e['id']]);
            $candidates = $c->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $c = $pdo->prepare("SELECT id, full_name, photo_path, role_title, pastor_term_years FROM candidates WHERE election_id = :eid ORDER BY id DESC LIMIT 1");
            $c->execute([':eid' => $e['id']]);
            $candidates = $c->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->view('voter/ballot.php', [
            'csrf'                => $this->services['csrf']->token(),
            'election'            => $e,
            'scrutiny'            => $scrutiny,
            'candidates'          => $candidates,
            'workflowLocked'      => $workflowLocked,
            'presenceRegistered'  => $presenceRegistered,
            'myCpf'               => $myCpf,
            'myName'              => $myName,
            'workflowStatus'      => (string)$e['status'],
        ]);
    }

    public function confirmPresence(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $userId = (int)$this->services['auth']->userId();
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $pdo = $this->services['pdo'];
        $e = $pdo->prepare("SELECT id FROM elections WHERE status IN ('OPEN','aberta_para_presenca','aberta_para_votacao') AND church_id = :cid ORDER BY id DESC LIMIT 1");
        $e->execute([':cid' => $churchId]);
        $electionId = (int)$e->fetchColumn();
        if ($electionId <= 0) {
            throw new RuntimeException('Não há eleição aberta para registro de presença.');
        }

        $u = $pdo->prepare("SELECT name, cpf FROM users WHERE id = :id AND church_id = :cid LIMIT 1");
        $u->execute([':id' => $userId, ':cid' => $churchId]);
        $me = $u->fetch(PDO::FETCH_ASSOC) ?: [];
        $cpf = (string)($me['cpf'] ?? '');
        $nome = (string)($me['name'] ?? '');

        /** @var AttendanceService $attSvc */
        $attSvc = $this->services['attendance'];
        $attSvc->registerPresence($churchId, $electionId, $cpf, $nome, $userId);

        $this->response->redirect('/votar');
    }

    public function castPastor(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $scrutinyId = (int)$this->request->post('scrutiny_id', 0);
        $choice = (string)$this->request->post('choice', '');
        $cpfForm  = (string)$this->request->post('cpf', '');

        $userId = $this->services['auth']->userId();
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $eCheck = $this->services['pdo']->prepare("SELECT id FROM elections WHERE id = :eid AND church_id = :cid");
        $eCheck->execute([':eid' => $electionId, ':cid' => $churchId]);
        if (!$eCheck->fetchColumn()) {
            throw new RuntimeException('Eleição não encontrada para a sua Igreja.');
        }

        $stmt = $this->services['pdo']->prepare("SELECT cpf FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $cpfDb = (string)$stmt->fetchColumn();

        $cpfFormNorm = AttendanceService::normalizeCpf($cpfForm);
        $cpfDbNorm   = AttendanceService::normalizeCpf($cpfDb);
        if ($cpfFormNorm !== '' && $cpfFormNorm !== $cpfDbNorm) {
            throw new RuntimeException('CPF informado não corresponde ao usuário logado.');
        }

        $cpfUsed = $cpfDbNorm !== '' ? $cpfDbNorm : $cpfFormNorm;
        if ($cpfUsed === '') {
            throw new RuntimeException('CPF inválido ou não cadastrado.');
        }

        $voteTx = $this->services['vote_tx'];
        $out = $voteTx->castPastorVote($electionId, $scrutinyId, $cpfUsed, $choice);

        if (!empty($out['closed_now'])) {
            $this->services['scrutiny_close']->closeAndCompute($electionId, $scrutinyId);
        }

        $stmt = $this->services['pdo']->prepare("SELECT public_key FROM elections WHERE id = :id");
        $stmt->execute([':id' => $electionId]);
        $key = $stmt->fetchColumn();

        $this->response->redirect('/dashboard.php?key=' . urlencode((string)$key));
    }

    public function castOfficers(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $scrutinyId = (int)$this->request->post('scrutiny_id', 0);
        $cpfForm  = (string)$this->request->post('cpf', '');

        $userId = $this->services['auth']->userId();
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $eCheck = $this->services['pdo']->prepare("SELECT id FROM elections WHERE id = :eid AND church_id = :cid");
        $eCheck->execute([':eid' => $electionId, ':cid' => $churchId]);
        if (!$eCheck->fetchColumn()) {
            throw new RuntimeException('Eleição não encontrada para a sua Igreja.');
        }

        $stmt = $this->services['pdo']->prepare("SELECT cpf FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $cpfDb = (string)$stmt->fetchColumn();

        $cpfFormNorm = AttendanceService::normalizeCpf($cpfForm);
        $cpfDbNorm   = AttendanceService::normalizeCpf($cpfDb);
        if ($cpfFormNorm !== '' && $cpfFormNorm !== $cpfDbNorm) {
            throw new RuntimeException('CPF informado não corresponde ao usuário logado.');
        }

        $cpfUsed = $cpfDbNorm !== '' ? $cpfDbNorm : $cpfFormNorm;
        if ($cpfUsed === '') {
            throw new RuntimeException('CPF inválido ou não cadastrado.');
        }

        $ids = $_POST['candidate_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }

        $voteTx = $this->services['vote_tx'];
        $out = $voteTx->castOfficersVote($electionId, $scrutinyId, $cpfUsed, $ids);

        if (!empty($out['closed_now'])) {
            $this->services['scrutiny_close']->closeAndCompute($electionId, $scrutinyId);
        }

        $stmt = $this->services['pdo']->prepare("SELECT public_key FROM elections WHERE id = :id");
        $stmt->execute([':id' => $electionId]);
        $key = $stmt->fetchColumn();

        $this->response->redirect('/dashboard.php?key=' . urlencode((string)$key));
    }
}