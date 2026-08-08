<?php

declare(strict_types=1);

/**
 * Deploy manual por URL (cPanel hospedagem compartilhada sem Git Version Control)
 *
 * ------------------------------------------------------------------
 * 1ª COISA A FAZER — Configurar a chave no seu eleicao.ini (cPanel):
 * ------------------------------------------------------------------
 *   Abra o arquivo /home2/coninfom/eleicao.ini (FORA da pasta pública)
 *   e adicione a seção [deploy] EXATAMENTE como abaixo (troque o valor
 *   por uma senha forte — letras/números/símbolos, 40+ chars):
 *
 *      [deploy]
 *      key = "TroqueEstaSenhaPorUmaMuitoForteCom40CaracteresOuMais_XyZ9!"
 *      branch = "main"
 *      webhook = "1"
 *
 *   Como o arquivo eleicao.ini JÁ está bloqueado no .gitignore, a sua
 *   chave de deploy NUNCA será commitada para o GitHub — 100% seguro.
 *
 * ------------------------------------------------------------------
 * MODO DE USAR (depois de configurar o eleicao.ini):
 * ------------------------------------------------------------------
 *   Certifique-se de que /home2/coninfom/public_html/eleicao já é um
 *   repo clonado (clone uma única vez via Terminal ou Método 2).
 *
 *   Deploy MANUAL por URL:
 *      https://seusite.com.br/eleicao/deploy.php?key=SUA_CHAVE_AQUI
 *
 *   Deploy AUTOMÁTICO via GitHub Webhook:
 *     1. GitHub → Repo → Settings → Webhooks → Add webhook
 *     2. Payload URL:
 *        https://seusite.com.br/eleicao/deploy.php?key=SUA_CHAVE_AQUI&github=1
 *     3. Content-type: application/json
 *     4. Secret: (deixe vazio, usamos o ?key=)
 *     5. Which events: Just the push event
 *     6. Active: ✓ → Add webhook
 *     Pronto! Todo push → deploy automático no cPanel.
 */

/* --------------------------------------------------------------
 * VALORES DEFAULT (usados APENAS se não encontrar o eleicao.ini)
 * -------------------------------------------------------------- */
const REPO_DIR        = __DIR__ . '/..';
const GIT_BIN         = '/usr/bin/git';
const DEFAULT_BRANCH  = 'main';
const DEFAULT_KEY     = '__DEPLOY_KEY_NOT_CONFIGURED__USE_ELEICAO_INI__';
const LOG_LIMIT       = 60;

/* --------------------------------------------------------------
 * Carrega chave e branch do eleicao.ini (fora da pasta pública)
 * -------------------------------------------------------------- */
$deployKey    = DEFAULT_KEY;
$mainBranch   = DEFAULT_BRANCH;
$allowWebhook = true;

$iniCandidates = [
    __DIR__ . '/../eleicao.ini',
    __DIR__ . '/../app/Config/eleicao.ini',
    (dirname(__DIR__, 2) ?: '') . '/eleicao.ini',
];
foreach ($iniCandidates as $ini) {
    if ($ini && is_file($ini)) {
        $data = @parse_ini_file($ini, true);
        if (is_array($data)) {
            if (!empty($data['deploy']['key']) && is_string($data['deploy']['key'])) {
                $deployKey = $data['deploy']['key'];
            }
            if (!empty($data['deploy']['branch']) && is_string($data['deploy']['branch'])) {
                $mainBranch = trim($data['deploy']['branch']);
            }
            if (isset($data['deploy']['webhook'])) {
                $allowWebhook = !in_array((string)$data['deploy']['webhook'], ['0', '', 'false', 'off', 'no'], true);
            }
            break;
        }
    }
}
define('DEPLOY_KEY',    $deployKey);
define('MAIN_BRANCH',   $mainBranch);
define('ALLOW_WEBHOOK', $allowWebhook);

header('Content-Type: text/html; charset=utf-8');

$isCli = (PHP_SAPI === 'cli');
$key   = '';
$isGh  = false;
if (!$isCli) {
    $key  = is_string($_GET['key'] ?? null) ? (string)$_GET['key'] : '';
    $isGh = (isset($_GET['github']) && ($_GET['github'] === '1' || $_GET['github'] === 'true'));
}

if ($isGh && ALLOW_WEBHOOK) {
    // Webhook GitHub: responde cedo com 200 para não estourar timeout deles
    http_response_code(202);
    while (ob_get_level()) ob_end_clean();
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request')) {
        session_write_close();
        fastcgi_finish_request();
    }
} elseif (!$isCli && !hash_equals(DEPLOY_KEY, $key)) {
    http_response_code(401);
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>401</title>';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1"></head>';
    echo '<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;';
    echo 'background:#F9FAFB;color:#1F2937;padding:48px 16px;display:flex;justify-content:center;">';
    echo '<div style="max-width:520px;background:#fff;border:1px solid #E5E7EB;border-radius:14px;';
    echo 'box-shadow:0 6px 16px rgba(6,47,59,.09);padding:32px 28px;text-align:center;">';
    echo '<h2 style="margin:0 0 8px 0;color:#DC2626;">401 — Chave incorreta</h2>';
    echo '<p style="color:#6B7280;margin:0 0 20px 0;">Informe o parâmetro <code style="background:#F3F4F6;padding:2px 6px;border-radius:6px;">?key=...</code> correto.</p>';
    echo '<a style="display:inline-block;padding:10px 16px;background:#004AAD;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;" href="/">Voltar ao início</a>';
    echo '</div></body></html>';
    exit(1);
}

function log_line(string $label, string $msg, bool $ok = true): void
{
    $color = $ok ? 'color:#166534;' : 'color:#991B1B;';
    echo '<div style="margin:4px 0;padding:4px 0;border-bottom:1px dashed #E5E7EB;">';
    echo '<span style="font-weight:700;', $color, '">', htmlspecialchars($label), '</span> ';
    echo '<span style="color:#374151;">', htmlspecialchars($msg), '</span>';
    echo "</div>\n";
}

if (!$isCli) {
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
    echo '<title>Deploy — Coninfoms Eleição</title>';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<style>body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#F9FAFB;color:#1F2937;margin:0;padding:28px 16px;}';
    echo '.wrap{max-width:880px;margin:0 auto;}.card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;box-shadow:0 6px 16px rgba(6,47,59,.09);padding:26px 24px;margin-bottom:18px;}';
    echo 'h1{margin:0 0 4px 0;color:#004AAD;font-size:22px;}h2{margin:18px 0 8px 0;font-size:16px;color:#111827;}';
    echo '.pill{display:inline-block;padding:6px 12px;border-radius:999px;font-weight:600;font-size:12px;}';
    echo '.pill--ok{background:#DCFCE7;color:#166534;border:1px solid #BBF7D0;}';
    echo '.pill--fail{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;}';
    echo '.pill--info{background:#DBEAFE;color:#1E3A8A;border:1px solid #BFDBFE;}';
    echo 'pre{background:#111827;color:#E5E7EB;padding:14px;border-radius:10px;overflow-x:auto;max-height:500px;font-size:13px;line-height:1.5;}';
    echo 'code{background:#F3F4F6;padding:2px 6px;border-radius:6px;font-size:13px;}</style>';
    echo '</head><body><div class="wrap">';
    echo '<div class="card"><h1>🔄 Deploy do Repositório Eleição</h1>';
    echo '<p style="margin:0;color:#6B7280;">Horário (servidor Coninfoms): <strong>', date('d/m/Y H:i:s'), '</strong></p>';
    echo '</div><div class="card">';
}

// 1. Verifica diretório do repositório
$repoDir = realpath(REPO_DIR);
if (!$repoDir || !is_dir($repoDir . '/.git')) {
    log_line('✗ Repo dir inválido', $repoDir ?: REPO_DIR, false);
    if (!$isCli) exit('</div></div></body></html>');
    exit(1);
}
log_line('✓ Diretório do repo', $repoDir);

// 2. Verifica binário do git
if (!@is_executable(GIT_BIN) && !file_exists(GIT_BIN)) {
    log_line('✗ Git não encontrado em', GIT_BIN, false);
    echo '<div style="margin-top:10px;padding:12px 14px;background:#FEF3C7;color:#92400E;border-radius:10px;border:1px solid #FDE68A;">';
    echo '<strong>Alternativa:</strong> o cPanel bloqueou o binário do git. Use o <strong>Método 4</strong> (deploy manual por ZIP) no guia.';
    echo '</div>';
    if (!$isCli) exit('</div></div></body></html>');
    exit(1);
}
log_line('✓ Git binário', GIT_BIN);

chdir($repoDir);

// 3. Status atual (antes do pull)
$cmdStatus = escapeshellarg(GIT_BIN) . ' status --short --branch 2>&1';
exec($cmdStatus, $statusOut, $statusCode);
log_line('✓ Branch / diffs (antes)', trim(implode("\n", array_slice($statusOut, 0, 5))) ?: 'clean', $statusCode === 0);

// 4. Fetch + Pull (com --no-rebase para segurança em shared host)
$cmds = [
    'Fetch origin'   => escapeshellarg(GIT_BIN) . ' fetch --all --prune 2>&1',
    'Hard reset'     => escapeshellarg(GIT_BIN) . ' reset --hard origin/' . MAIN_BRANCH . ' 2>&1',
    'Pull (garantia)' => escapeshellarg(GIT_BIN) . ' pull --ff-only origin ' . MAIN_BRANCH . ' 2>&1',
];

$allOk = true;
foreach ($cmds as $label => $cmd) {
    $output = [];
    $ret    = 0;
    exec($cmd, $output, $ret);
    $tail   = array_slice($output, -LOG_LIMIT);
    $txt    = trim(implode("\n", $tail)) ?: '(sem output)';
    $ok     = $ret === 0 || str_contains($txt, 'Already up to date') || str_contains($txt, 'up to date');
    if (!$ok) $allOk = false;
    log_line(($ok ? '✓ ' : '✗ ') . $label, $txt, $ok);
}

// 5. Commit log (últimos 3 para confirmação)
$logCmd = escapeshellarg(GIT_BIN) . " log -3 --oneline 2>&1";
exec($logCmd, $logOut, $_);
if (!$isCli) {
    echo '<h2>📜 Últimos commits aplicados</h2>';
    echo '<pre>', htmlspecialchars(implode("\n", $logOut) ?: '(sem log)'), '</pre>';
}

// 6. Final
if (!$isCli) {
    $pillClass = $allOk ? 'pill--ok' : 'pill--fail';
    $pillTxt   = $allOk ? 'DEPLOY CONCLUÍDO ✓' : 'HOUVE ALGUM ERRO ✗';
    echo '<div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">';
    echo '<span class="pill ', $pillClass, '">', $pillTxt, '</span>';
    echo '<span class="pill pill--info">branch: ', MAIN_BRANCH, '</span>';
    echo '</div></div></div></body></html>';
}
exit($allOk ? 0 : 2);
