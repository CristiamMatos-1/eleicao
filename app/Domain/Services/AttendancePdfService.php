<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Libraries\FPDF\FPDF;
use PDO;

final class AttendancePdfService
{
    private const LINE_HEIGHT_MM = 11.5;

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function generate(array $election, int $churchId, int $electionId): void
    {
        $rows = $this->fetchRows($churchId, $electionId);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);

        $pdf->AddPage();

        $entityName = $this->fetchEntityName($election, $churchId);
        $assemblyType = strtoupper((string)($election['assembly_type'] ?? 'ORDINARIA')) === 'EXTRAORDINARIA'
            ? 'EXTRAORDINÁRIA'
            : 'ORDINÁRIA';

        $this->renderHeader($pdf, $election, count($rows), $entityName, $assemblyType);
        $this->renderTableHeader($pdf);

        $line = 1;
        foreach ($rows as $r) {
            $this->renderRow($pdf, $line, (string)($r['nome'] ?? ''), (string)($r['cpf'] ?? ''));
            $line++;
        }

        $title = 'Lista_de_Presenca_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($election['title'] ?? 'lista')) . '.pdf';
        $pdf->Output('D', $title, true);
    }

    private function fetchEntityName(array $election, int $churchId): string
    {
        $fromElection = trim((string)(($election['church_legal_name'] ?? '') !== ''
            ? $election['church_legal_name']
            : ($election['entity_name'] ?? ($election['church_name'] ?? ''))));
        if ($fromElection !== '') {
            return $fromElection;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT name, legal_name FROM churches WHERE id = :cid LIMIT 1");
            $stmt->execute([':cid' => $churchId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $legal = trim((string)($row['legal_name'] ?? ''));
            if ($legal !== '') {
                return $legal;
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        } catch (\Throwable) {
        }
        return '';
    }

    private function fetchRows(int $churchId, int $electionId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.nome, p.cpf
                 FROM eleicao_presencas p
                 WHERE p.church_id = :cid AND p.eleicao_id = :eid
                 ORDER BY p.nome ASC"
            );
            $stmt->execute([':cid' => $churchId, ':eid' => $electionId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            $rows = [];
        }

        if ($rows !== []) {
            return $rows;
        }

        $e = $this->pdo->prepare("SELECT type FROM elections WHERE id = :eid AND church_id = :cid LIMIT 1");
        $e->execute([':eid' => $electionId, ':cid' => $churchId]);
        $type = (string)$e->fetchColumn();

        if ($type === 'DIRETORIA') {
            $base = $this->pdo->prepare("
                SELECT u.name AS nome, u.cpf
                FROM users u
                INNER JOIN election_voters ev ON ev.user_id = u.id AND ev.election_id = :eid
                WHERE u.role = 'ELEITOR' AND u.approved = 1 AND u.active = 1
                ORDER BY u.name ASC
            ");
            $base->execute([':eid' => $electionId]);
        } else {
            $base = $this->pdo->prepare("
                SELECT name AS nome, cpf
                FROM users
                WHERE role = 'ELEITOR' AND approved = 1 AND active = 1 AND church_id = :cid
                ORDER BY name ASC
            ");
            $base->execute([':cid' => $churchId]);
        }
        return $base->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function renderHeader(FPDF $pdf, array $election, int $totalRows, string $entityName, string $assemblyType): void
    {
        $pdf->SetFont('Times', 'B', 14);
        $wAll = $pdf->GetPageWidth() - 40;

        $pdf->Cell($wAll, self::LINE_HEIGHT_MM, $this->prepareText('LISTA DE PRESENÇA DA ASSEMBLEIA GERAL ' . $assemblyType), 0, 1, 'C');

        if ($entityName !== '') {
            $pdf->SetFont('Times', 'B', 13);
            $pdf->MultiCell($wAll, self::LINE_HEIGHT_MM, $this->prepareText('DO ' . $entityName), 0, 'C');
        }

        $title = (string)($election['title'] ?? '');
        if ($title !== '') {
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell($wAll, self::LINE_HEIGHT_MM, $this->prepareText($title), 0, 1, 'C');
        }

        $meta = [];
        if (!empty($election['election_date'])) {
            $dt = \DateTime::createFromFormat('Y-m-d', (string)$election['election_date']);
            if ($dt !== false) {
                $meta[] = 'Data: ' . $dt->format('d/m/Y');
            }
        }
        if (!empty($election['type'])) {
            $meta[] = 'Tipo: ' . $this->prepareText((string)$election['type']);
        }
        $meta[] = 'Total: ' . $totalRows . ' eleitor(es)';

        if ($meta !== []) {
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell($wAll, self::LINE_HEIGHT_MM, implode('  |  ', $meta), 0, 1, 'C');
        }

        $pdf->Ln(self::LINE_HEIGHT_MM * 0.6);
    }

    private function renderTableHeader(FPDF $pdf): void
    {
        $h = self::LINE_HEIGHT_MM;
        $pdf->SetFont('Times', 'B', 12);

        $wNum = 12;
        $wCpf = 35;
        $wSign = 50;
        $pageW = $pdf->GetPageWidth() - 40;
        $wName = $pageW - $wNum - $wCpf - $wSign;

        $xStart = $pdf->GetX();
        $pdf->Cell($wNum, $h, $this->prepareText('Nº'), 1, 0, 'C');
        $pdf->Cell($wName, $h, $this->prepareText('Nome do Eleitor'), 1, 0, 'L');
        $pdf->Cell($wCpf, $h, $this->prepareText('CPF'), 1, 0, 'C');
        $pdf->Cell($wSign, $h, $this->prepareText('Assinatura'), 1, 1, 'C');
    }

    private function renderRow(FPDF $pdf, int $lineNumber, string $nome, string $cpfRaw): void
    {
        $h = self::LINE_HEIGHT_MM;
        $pdf->SetFont('Times', '', 12);

        $wNum = 12;
        $wCpf = 35;
        $wSign = 50;
        $pageW = $pdf->GetPageWidth() - 40;
        $wName = $pageW - $wNum - $wCpf - $wSign;

        $cpf = $this->formatCpf($cpfRaw);

        $pdf->Cell($wNum, $h, (string)$lineNumber, 1, 0, 'C');
        $pdf->Cell($wName, $h, $this->prepareText($nome), 1, 0, 'L');
        $pdf->Cell($wCpf, $h, $cpf, 1, 0, 'C');
        $pdf->Cell($wSign, $h, '', 1, 1, 'C');
    }

    private function formatCpf(string $cpf): string
    {
        $digits = (string)preg_replace('/\D/', '', $cpf ?? '');
        if (strlen($digits) !== 11) {
            return $digits;
        }
        return substr($digits, 0, 3) . '.' .
            substr($digits, 3, 3) . '.' .
            substr($digits, 6, 3) . '-' .
            substr($digits, 9, 2);
    }

    private function prepareText(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_convert_encoding')) {
            $conv = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if ($conv !== false) {
                return $conv;
            }
        }
        return $s;
    }
}
