<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use PDO;
use RuntimeException;

final class VoteController extends Controller
{
    public function showBallot(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);

        $pdo = $this->services['pdo'];
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $e = $pdo->prepare("SELECT id, type, title, status, vacancies FROM elections WHERE status = 'OPEN' AND church_id = :cid ORDER BY id DESC LIMIT 1");
        $e->execute([':cid' => $churchId]);
        $e = $e->fetch(PDO::FETCH_ASSOC);

        if (!$e) {
            throw new RuntimeException('Não há eleição aberta.');
        }

        $s = $pdo->prepare("SELECT id, number, status FROM scrutiniums WHERE election_id = :eid AND status = 'OPEN' ORDER BY number DESC LIMIT 1");
        $s->execute([':eid' => $e['id']]);
        $scrutiny = $s->fetch(PDO::FETCH_ASSOC);

        if (!$scrutiny) {
            throw new RuntimeException('Não há escrutínio aberto.');
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
            'csrf' => $this->services['csrf']->token(),
            'election' => $e,
            'scrutiny' => $scrutiny,
            'candidates' => $candidates,
        ]);
    }

    public function castPastor(): void
    {
        $this->services['auth']->requireRole(['ELEITOR']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $electionId = (int)$this->request->post('election_id', 0);
        $scrutinyId = (int)$this->request->post('scrutiny_id', 0);
        $choice = (string)$this->request->post('choice', '');

        $userId = $this->services['auth']->userId();
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $eCheck = $this->services['pdo']->prepare("SELECT id FROM elections WHERE id = :eid AND church_id = :cid");
        $eCheck->execute([':eid' => $electionId, ':cid' => $churchId]);
        if (!$eCheck->fetchColumn()) {
            throw new RuntimeException('Eleição não encontrada para a sua Igreja.');
        }

        $stmt = $this->services['pdo']->prepare("SELECT cpf FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $cpf = $stmt->fetchColumn();

        $voteTx = $this->services['vote_tx'];
        $out = $voteTx->castPastorVote($electionId, $scrutinyId, (string)$cpf, $choice);

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

        $userId = $this->services['auth']->userId();
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $eCheck = $this->services['pdo']->prepare("SELECT id FROM elections WHERE id = :eid AND church_id = :cid");
        $eCheck->execute([':eid' => $electionId, ':cid' => $churchId]);
        if (!$eCheck->fetchColumn()) {
            throw new RuntimeException('Eleição não encontrada para a sua Igreja.');
        }

        $stmt = $this->services['pdo']->prepare("SELECT cpf FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $cpf = $stmt->fetchColumn();

        $ids = $_POST['candidate_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }

        $voteTx = $this->services['vote_tx'];
        $out = $voteTx->castOfficersVote($electionId, $scrutinyId, (string)$cpf, $ids);

        if (!empty($out['closed_now'])) {
            $this->services['scrutiny_close']->closeAndCompute($electionId, $scrutinyId);
        }

        $stmt = $this->services['pdo']->prepare("SELECT public_key FROM elections WHERE id = :id");
        $stmt->execute([':id' => $electionId]);
        $key = $stmt->fetchColumn();

        $this->response->redirect('/dashboard.php?key=' . urlencode((string)$key));
    }
}