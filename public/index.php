<?php

declare(strict_types=1);

// TEMPORÁRIO: Exibir erros na tela (Remova depois que tudo estiver funcionando)
ini_set('display_errors', '1');
error_reporting(E_ALL);

use App\Core\App;
use App\Controllers\AuthController;
use App\Controllers\VoteController;
use App\Controllers\AdminController;
use App\Controllers\ElectionController;
use App\Controllers\SuperAdminController;

$services = require __DIR__ . '/../app/bootstrap.php';

$app = new App($services);

$app->get('/', function ($req, $res, $svc) {
    $res->redirect('/login');
});

$app->get('/login', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->showElectorLogin());
$app->post('/login', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->doElectorLogin());
$app->get('/register', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->showRegister());
$app->post('/register', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->doRegister());
$app->post('/logout', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->logout());

$app->get('/admin/login', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->showAdminLogin());
$app->post('/admin/login', fn($req, $res, $svc) => (new AuthController($req, $res, $svc))->doAdminLogin());

$app->get('/votar', fn($req, $res, $svc) => (new VoteController($req, $res, $svc))->showBallot());
$app->get('/voto', fn($req, $res, $svc) => $res->redirect('/votar'));
$app->get('/voto/', fn($req, $res, $svc) => $res->redirect('/votar'));
$app->post('/votar/pastor', fn($req, $res, $svc) => (new VoteController($req, $res, $svc))->castPastor());
$app->post('/votar/oficiais', fn($req, $res, $svc) => (new VoteController($req, $res, $svc))->castOfficers());

$app->get('/admin', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->home());
$app->post('/admin/registrations/open', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->openRegistrations());
$app->post('/admin/registrations/close', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->closeRegistrations());
$app->post('/admin/elector/approve', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->approveElector());
$app->post('/admin/elector/add', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->addElector());
$app->post('/admin/elector/delete', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->deleteElector());
$app->post('/admin/user/add', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->addSystemUser());
$app->post('/admin/user/delete', fn($req, $res, $svc) => (new AdminController($req, $res, $svc))->deleteSystemUser());

$app->get('/admin/elections/new', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->newForm());
$app->post('/admin/elections', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->create());
$app->get('/admin/elections/manage', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->manage());
$app->post('/admin/elections/close', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->closeElection());
$app->post('/admin/elections/scrutiny/reset', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->resetScrutiny());
$app->post('/admin/elections/candidate/add', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->addCandidate());
$app->post('/admin/elections/candidate/delete', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->deleteCandidate());
$app->post('/admin/elections/delete', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->deleteElection());
$app->post('/admin/elections/candidate/edit', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->editCandidate());
$app->post('/admin/elections/config/edit', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->editConfig());
$app->post('/admin/elections/accreditation/toggle', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->toggleAccreditation());

$app->get('/admin/elections/attendance', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->attendanceList());
$app->get('/admin/elections/scrutiny/next', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->openNextScrutinyForm());
$app->post('/admin/elections/scrutiny/next', fn($req, $res, $svc) => (new ElectionController($req, $res, $svc))->openNextScrutiny());

$app->get('/superadmin/churches', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->manageChurches());
$app->post('/superadmin/churches/add', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->addChurch());
$app->post('/superadmin/churches/edit', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->editChurch());
$app->post('/superadmin/churches/delete', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->deleteChurch());

$app->get('/superadmin/settings', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->settings());
$app->post('/superadmin/settings/update', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->updateSettings());
$app->post('/superadmin/users/add', fn($req, $res, $svc) => (new SuperAdminController($req, $res, $svc))->addSuperAdmin());

$app->run();