<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use PDO;

final class AdminController extends Controller
{
    public function home(): void
    {
        $this->services['auth']->requireRole(['ADMIN','CONDUTOR','SUPER_ADMIN']);

        $userId   = (int)($this->services['auth']->userId() ?? 0);
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $pdo = $this->services['pdo'];

        $authUser = null;
        if ($userId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $authUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $settings = $pdo->query("SELECT registration_open FROM registration_settings WHERE id = 1")
            ->fetch(PDO::FETCH_ASSOC);

        $anyAttendanceOpen = false;
        try {
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM elections WHERE status = 'aberta_para_presenca' LIMIT 1"
            );
            $anyAttendanceOpen = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            $anyAttendanceOpen = false;
        }

        $pending = $pdo->prepare(
            "SELECT id, name, cpf, created_at
             FROM users
             WHERE role = 'ELEITOR' AND approved = 0 AND active = 1 AND church_id = :cid
             ORDER BY created_at ASC
             LIMIT 50"
        );
        $pending->execute([':cid' => $churchId]);
        $pending = $pending->fetchAll(PDO::FETCH_ASSOC);

        $approvedElectors = $pdo->prepare(
            "SELECT id, name, cpf, created_at
             FROM users
             WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid
             ORDER BY name ASC"
        );
        $approvedElectors->execute([':cid' => $churchId]);
        $approvedElectors = $approvedElectors->fetchAll(PDO::FETCH_ASSOC);

        $systemUsers = $pdo->prepare(
            "SELECT id, name, email, role, created_at
             FROM users
             WHERE role IN ('ADMIN', 'CONDUTOR') AND active = 1 AND church_id = :cid
             ORDER BY name ASC"
        );
        $systemUsers->execute([':cid' => $churchId]);
        $systemUsers = $systemUsers->fetchAll(PDO::FETCH_ASSOC);

        $elections = $pdo->prepare(
            "SELECT id, type, title, election_date, expected_voters, vacancies, status, public_key, opened_at, closed_at
             FROM elections
             WHERE church_id = :cid
             ORDER BY id DESC
             LIMIT 30"
        );
        $elections->execute([':cid' => $churchId]);
        $elections = $elections->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/home.php', [
            'csrf' => $this->services['csrf']->token(),
            'registration_open' => ((int)($settings['registration_open'] ?? 0) === 1) || $anyAttendanceOpen,
            'registration_open_by_flag' => (int)($settings['registration_open'] ?? 0) === 1,
            'registration_open_by_attendance' => $anyAttendanceOpen,
            'pending' => $pending,
            'approvedElectors' => $approvedElectors,
            'systemUsers' => $systemUsers,
            'elections' => $elections,
            'authUser' => $authUser,
            'authUserName' => (string)($authUser['name'] ?? ''),
            'authUserRole' => (string)($authUser['role'] ?? ''),
        ]);
    }

    public function openRegistrations(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $pdo = $this->services['pdo'];
        $pdo->exec("UPDATE registration_settings SET registration_open = 1 WHERE id = 1");

        $this->response->redirect('/admin');
    }

    public function closeRegistrations(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $pdo = $this->services['pdo'];
        $pdo->exec("UPDATE registration_settings SET registration_open = 0 WHERE id = 1");

        $this->response->redirect('/admin');
    }

    public function approveElector(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $id = (int)$this->request->post('user_id', 0);
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare("UPDATE users SET approved = 1 WHERE id = :id AND role = 'ELEITOR' AND church_id = :cid");
        $stmt->execute([':id' => $id, ':cid' => $churchId]);

        $this->response->redirect('/admin');
    }

    public function addElector(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $name = trim((string)$this->request->post('name', ''));
        $cpf = preg_replace('/\D+/', '', (string)$this->request->post('cpf', ''));
        
        if ($name === '' || !\App\Helpers\CpfValidator::isValid($cpf)) {
            throw new \RuntimeException('Nome ou CPF inválidos.');
        }

        $pdo = $this->services['pdo'];
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (church_id, role, name, cpf, approved, active) VALUES (:cid, 'ELEITOR', :name, :cpf, 1, 1)");
            $stmt->execute([':cid' => $churchId, ':name' => $name, ':cpf' => $cpf]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('CPF já cadastrado.');
            }
            throw $e;
        }
        $this->response->redirect('/admin');
    }

    public function deleteElector(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        $id = (int)$this->request->post('user_id', 0);
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'ELEITOR' AND church_id = :cid");
        $stmt->execute([':id' => $id, ':cid' => $churchId]);
        
        $this->response->redirect('/admin');
    }

    public function addSystemUser(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        
        $name = trim((string)$this->request->post('name', ''));
        $email = trim((string)$this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');
        $role = (string)$this->request->post('role', '');
        
        if ($name === '' || $email === '' || $password === '') {
            throw new \RuntimeException('Preencha todos os campos obrigatórios.');
        }

        if (!in_array($role, ['ADMIN', 'CONDUTOR'], true)) {
            throw new \RuntimeException('Papel de usuário inválido.');
        }

        $pdo = $this->services['pdo'];
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (church_id, role, name, email, password_hash, approved, active) VALUES (:cid, :role, :name, :email, :hash, 1, 1)");
            $stmt->execute([':cid' => $churchId, ':role' => $role, ':name' => $name, ':email' => $email, ':hash' => $hash]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('Este e-mail já está cadastrado.');
            }
            throw $e;
        }
        
        $this->response->redirect('/admin');
    }

    public function deleteSystemUser(): void
    {
        $this->services['auth']->requireRole(['ADMIN','SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));
        $id = (int)$this->request->post('user_id', 0);
        
        // Prevent deleting oneself
        if ($id === $this->services['auth']->userId()) {
            throw new \RuntimeException('Você não pode excluir a si mesmo.');
        }
        $churchId = (int)($_SESSION[\App\Core\Auth::SESS_CHURCH_ID] ?? 0);

        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role IN ('ADMIN', 'CONDUTOR') AND church_id = :cid");
        $stmt->execute([':id' => $id, ':cid' => $churchId]);
        
        $this->response->redirect('/admin');
    }
}