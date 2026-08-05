<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use RuntimeException;
use PDO;

final class SuperAdminController extends Controller
{
    public function manageChurches(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);

        $pdo = $this->services['pdo'];
        $churches = $pdo->query(
            "SELECT c.*, 
             (SELECT email FROM users u WHERE u.church_id = c.id AND u.role = 'ADMIN' ORDER BY u.id ASC LIMIT 1) as admin_email
             FROM churches c 
             ORDER BY c.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('superadmin/churches.php', [
            'csrf' => $this->services['csrf']->token(),
            'churches' => $churches
        ]);
    }

    public function updateChurchAdminPassword(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $churchId = (int)$this->request->post('church_id', 0);
        $userId = (int)$this->request->post('user_id', 0);
        $password = (string)$this->request->post('password', '');

        if ($churchId <= 0 || $userId <= 0 || $password === '') {
            throw new RuntimeException('Dados inválidos para atualização de senha.');
        }

        if (!$this->services['auth']->canManageChurchAdminPassword($churchId)) {
            throw new RuntimeException('Você não tem permissão para alterar esta senha.');
        }

        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare(
            "UPDATE users
             SET password_hash = :hash
             WHERE id = :id AND church_id = :church_id AND role = 'ADMIN'"
        );
        $stmt->execute([
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $userId,
            ':church_id' => $churchId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Administrador não encontrado para esta igreja.');
        }

        $this->response->redirect('/superadmin/churches?password_updated=1');
    }

    public function addChurch(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $name = trim((string)$this->request->post('name', ''));
        $adminEmail = trim((string)$this->request->post('admin_email', ''));
        $adminPassword = (string)$this->request->post('admin_password', '');

        if ($name === '' || $adminEmail === '' || $adminPassword === '') {
            throw new RuntimeException('Nome da igreja, e-mail e senha do administrador são obrigatórios.');
        }

        // Generate slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $pdo = $this->services['pdo'];

        // Ensure slug uniqueness
        $check = $pdo->prepare("SELECT COUNT(*) FROM churches WHERE slug = :slug");
        $check->execute([':slug' => $slug]);
        if ($check->fetchColumn() > 0) {
            $slug .= '-' . uniqid();
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO churches (name, slug) VALUES (:name, :slug)");
            $stmt->execute([':name' => $name, ':slug' => $slug]);
            $churchId = (int)$pdo->lastInsertId();

            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare(
                "INSERT INTO users (church_id, role, name, email, password_hash, approved, active) 
                 VALUES (:cid, 'ADMIN', 'Administrador Local', :email, :hash, 1, 1)"
            );
            $stmtUser->execute([
                ':cid' => $churchId,
                ':email' => $adminEmail,
                ':hash' => $hash
            ]);

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Este e-mail já está em uso por outro usuário.');
            }
            throw $e;
        }

        $this->response->redirect('/superadmin/churches');
    }

    public function editChurch(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $id = (int)$this->request->post('church_id', 0);
        $name = trim((string)$this->request->post('name', ''));

        if ($id <= 0 || $name === '') {
            throw new RuntimeException('Dados inválidos para edição.');
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $pdo = $this->services['pdo'];
        $check = $pdo->prepare("SELECT COUNT(*) FROM churches WHERE slug = :slug AND id != :id");
        $check->execute([':slug' => $slug, ':id' => $id]);
        if ($check->fetchColumn() > 0) {
            $slug .= '-' . uniqid();
        }

        $stmt = $pdo->prepare("UPDATE churches SET name = :name, slug = :slug WHERE id = :id");
        $stmt->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);

        $this->response->redirect('/superadmin/churches');
    }

    public function deleteChurch(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $id = (int)$this->request->post('church_id', 0);
        if ($id === 1) {
            throw new RuntimeException('Não é possível excluir a Igreja Sede (ID 1).');
        }

        $pdo = $this->services['pdo'];
        $stmt = $pdo->prepare("DELETE FROM churches WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $this->response->redirect('/superadmin/churches');
    }

    public function settings(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $pdo = $this->services['pdo'];
        
        $userId = $this->services['auth']->userId();
        $me = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
        $me->execute([':id' => $userId]);
        $me = $me->fetch(PDO::FETCH_ASSOC);

        $superAdmins = $pdo->query("SELECT id, name, email FROM users WHERE role = 'SUPER_ADMIN' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('superadmin/settings.php', [
            'csrf' => $this->services['csrf']->token(),
            'me' => $me,
            'superAdmins' => $superAdmins,
            'userId' => $userId
        ]);
    }

    public function updateSettings(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $userId = $this->services['auth']->userId();
        $name = trim((string)$this->request->post('name', ''));
        $email = trim((string)$this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');

        if ($name === '' || $email === '') {
            throw new RuntimeException('Nome e e-mail são obrigatórios.');
        }

        $pdo = $this->services['pdo'];

        // Check email uniqueness
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => $email, ':id' => $userId]);
        if ($check->fetchColumn() > 0) {
            throw new RuntimeException('Este e-mail já está em uso.');
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, password_hash = :hash WHERE id = :id");
            $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash, ':id' => $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            $stmt->execute([':name' => $name, ':email' => $email, ':id' => $userId]);
        }

        $this->response->redirect('/superadmin/settings?updated=1');
    }

    public function addSuperAdmin(): void
    {
        $this->services['auth']->requireRole(['SUPER_ADMIN']);
        $this->services['csrf']->validate($this->request->post('_csrf'));

        $name = trim((string)$this->request->post('name', ''));
        $email = trim((string)$this->request->post('email', ''));
        $password = (string)$this->request->post('password', '');

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Todos os campos são obrigatórios.');
        }

        $pdo = $this->services['pdo'];

        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetchColumn() > 0) {
            throw new RuntimeException('Este e-mail já está em uso.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (church_id, role, name, email, password_hash, approved, active) VALUES (1, 'SUPER_ADMIN', :name, :email, :hash, 1, 1)");
        $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash]);

        $this->response->redirect('/superadmin/settings?added=1');
    }
}
