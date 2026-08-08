<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use RuntimeException;

final class AuthController extends Controller
{
    public function showElectorLogin(): void
    {
        $pdo = $this->services['pdo'];
        $churches = $pdo->query("SELECT id, name FROM churches ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('auth/elector_login.php', [
            'csrf' => $this->services['csrf']->token(),
            'churches' => $churches
        ]);
    }

    public function showRegister(): void
    {
        $pdo = $this->services['pdo'];
        $settings = $pdo->query("SELECT registration_open FROM registration_settings WHERE id = 1")->fetch(\PDO::FETCH_ASSOC);
        $isOpenFlag = (int)($settings['registration_open'] ?? 0) === 1;

        $anyAttendanceOpen = false;
        try {
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM elections WHERE status = 'aberta_para_presenca' LIMIT 1"
            );
            $anyAttendanceOpen = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            $anyAttendanceOpen = false;
        }

        $isOpen = $isOpenFlag || $anyAttendanceOpen;

        $churches = $pdo->query("SELECT id, name FROM churches ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('auth/elector_register.php', [
            'csrf' => $this->services['csrf']->token(),
            'isOpen' => $isOpen,
            'churches' => $churches
        ]);
    }

    public function doRegister(): void
    {
        $this->services['csrf']->validate($this->request->post('_csrf'));
        $pdo = $this->services['pdo'];

        $settings = $pdo->query("SELECT registration_open FROM registration_settings WHERE id = 1")->fetch(\PDO::FETCH_ASSOC);
        $isOpenFlag = (int)($settings['registration_open'] ?? 0) === 1;

        $anyAttendanceOpen = false;
        try {
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM elections WHERE status = 'aberta_para_presenca' LIMIT 1"
            );
            $anyAttendanceOpen = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            $anyAttendanceOpen = false;
        }

        if (!$isOpenFlag && !$anyAttendanceOpen) {
            throw new RuntimeException('O período de cadastro está encerrado.');
        }

        $name = trim((string)$this->request->post('name', ''));
        $cpf = preg_replace('/\D+/', '', (string)$this->request->post('cpf', ''));
        $churchId = (int)$this->request->post('church_id', 0);

        if ($name === '' || !\App\Helpers\CpfValidator::isValid($cpf) || $churchId <= 0) {
            throw new RuntimeException('Dados inválidos. Verifique se selecionou a Igreja e digitou um CPF válido.');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO users (church_id, role, name, cpf, approved, active) VALUES (:cid, 'ELEITOR', :name, :cpf, 0, 1)");
            $stmt->execute([':cid' => $churchId, ':name' => $name, ':cpf' => $cpf]);
            $this->response->redirect('/login?registered=1');
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Este CPF já está cadastrado nesta Igreja.');
            }
            throw $e;
        }
    }

    public function doElectorLogin(): void
    {
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $cpf = (string)$this->request->post('cpf', '');

        $this->services['auth']->loginElectorByCpf($cpf);

        $this->response->redirect('/votar');
    }

    public function showAdminLogin(): void
    {
        $this->view('auth/admin_login.php', [
            'csrf' => $this->services['csrf']->token()
        ]);
    }

    public function doAdminLogin(): void
    {
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $email = (string)$this->request->post('email', '');
        $password = (string)$this->request->post('password', '');

        $this->services['auth']->loginAdminByEmailPassword($email, $password);

        $this->response->redirect('/admin');
    }

    public function logout(): void
    {
        $this->services['csrf']->validate($this->request->post('_csrf'));
        $this->services['auth']->logout();
        $this->response->redirect('/login');
    }
}