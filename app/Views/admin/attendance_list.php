<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Lista de Presença - <?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      color: #000;
      background: #fff;
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    .header h1 {
      margin: 0 0 10px 0;
      font-size: 24px;
    }
    .header p {
      margin: 0;
      font-size: 14px;
      color: #555;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }
    th, td {
      border: 1px solid #000;
      padding: 10px;
      text-align: left;
    }
    th {
      background: #f0f0f0;
      font-weight: bold;
    }
    .col-num { width: 5%; text-align: center; }
    .col-name { width: 45%; }
    .col-cpf { width: 20%; }
    .col-sign { width: 30%; }
    
    @media print {
      body { padding: 0; }
      .no-print { display: none !important; }
      @page { margin: 1cm; }
    }
  </style>
</head>
<body>
  <div class="no-print" style="margin-bottom: 20px; text-align: right;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Imprimir Lista</button>
  </div>

  <div class="header">
    <h1>Lista de Presença</h1>
    <h2><?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Data: <?= date('d/m/Y', strtotime($election['election_date'])) ?> | Tipo: <?= htmlspecialchars($election['type'], ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th class="col-name">Nome do Eleitor</th>
        <th class="col-cpf">CPF</th>
        <th class="col-sign">Assinatura</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($voters)): ?>
        <tr>
          <td colspan="4" style="text-align: center;">Nenhum eleitor habilitado para esta eleição.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($voters as $index => $v): ?>
          <tr>
            <td class="col-num"><?= $index + 1 ?></td>
            <td class="col-name"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="col-cpf">
              <?php
                // Format CPF nicely
                $cpf = preg_replace('/\D/', '', $v['cpf'] ?? '');
                if (strlen($cpf) === 11) {
                    echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                } else {
                    echo htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8');
                }
              ?>
            </td>
            <td class="col-sign"></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
