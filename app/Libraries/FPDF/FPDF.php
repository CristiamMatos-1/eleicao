<?php

declare(strict_types=1);

namespace App\Libraries\FPDF;

use RuntimeException;

final class FPDF
{
    private int $pageNo = 0;
    private int $n;
    private array $offsets;
    private string $buffer;
    private int $currentPageBufferStart;

    private float $pageWidth;
    private float $pageHeight;

    private float $lMargin;
    private float $tMargin;
    private float $rMargin;
    private float $bMargin;
    private float $lineWidth  = 0.2;

    private float $x;
    private float $y;
    private float $lasth;

    private array $fonts;
    private array $fontFiles;
    private ?int $currentFontKey = null;
    private float $fontSizePt;
    private bool $fontUnderline = false;

    private array $pages;
    private array $pageResources;
    private int $autoPageBreak;
    private float $pageBreakTrigger;

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        $this->offsets  = [];
        $this->n        = 0;
        $this->buffer   = '';
        $this->pages    = [];
        $this->fonts    = [];
        $this->fontFiles = [];
        $this->pageResources = [];
        $this->lasth    = 0;
        $this->currentPageBufferStart = 0;

        $size = strtoupper($size);
        if ($size === 'A4') {
            $ptW = 595.28;
            $ptH = 841.89;
        } else {
            $ptW = 595.28;
            $ptH = 841.89;
        }

        if ($unit === 'mm') {
            $this->pageWidth  = $ptW / 2.83465;
            $this->pageHeight = $ptH / 2.83465;
        } else {
            $this->pageWidth  = $ptW;
            $this->pageHeight = $ptH;
        }

        if (strtoupper($orientation) === 'L') {
            $tmp = $this->pageWidth;
            $this->pageWidth  = $this->pageHeight;
            $this->pageHeight = $tmp;
        }

        $this->lMargin = 10;
        $this->tMargin = 10;
        $this->rMargin = 10;
        $this->bMargin = 20;

        $this->x = $this->lMargin;
        $this->y = $this->tMargin;

        $this->autoPageBreak   = 1;
        $this->pageBreakTrigger = $this->pageHeight - $this->bMargin;
        $this->fontSizePt      = 12;
    }

    public function SetMargins(float $left, float $top, ?float $right = null): void
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right !== null) {
            $this->rMargin = $right;
        }
        if ($this->pageNo === 0) {
            $this->x = $left;
            $this->y = $top;
        }
    }

    public function SetAutoPageBreak(bool $auto, float $margin = 20): void
    {
        $this->autoPageBreak     = $auto ? 1 : 0;
        $this->bMargin           = $margin;
        $this->pageBreakTrigger  = $this->pageHeight - $margin;
    }

    public function AddPage(string $orientation = ''): void
    {
        if ($this->pageNo > 0) {
            $this->_endpage();
        }
        $this->_beginpage($orientation);
    }

    private function _beginpage(string $orientation): void
    {
        $this->pageNo++;
        $this->pages[$this->pageNo] = '';
        $this->n++;
        $this->currentPageBufferStart = strlen($this->buffer);

        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->fontFiles = [];
        $this->pageResources[$this->pageNo] = ['fonts' => [], 'annots' => []];
    }

    private function _endpage(): void
    {
    }

    public function GetPageWidth(): float
    {
        return $this->pageWidth;
    }

    public function GetPageHeight(): float
    {
        return $this->pageHeight;
    }

    public function GetX(): float
    {
        return $this->x;
    }

    public function SetX(float $x): void
    {
        if ($x >= 0) {
            $this->x = $x;
        } else {
            $this->x = $this->pageWidth + $x;
        }
    }

    public function GetY(): float
    {
        return $this->y;
    }

    public function SetY(float $y, bool $resetX = true): void
    {
        if ($y >= 0) {
            $this->y = $y;
        } else {
            $this->y = $this->pageHeight + $y;
        }
        if ($resetX) {
            $this->x = $this->lMargin;
        }
    }

    public function SetXY(float $x, float $y): void
    {
        $this->SetX($x);
        $this->SetY($y, false);
    }

    public function SetLineWidth(float $width): void
    {
        if ($width > 0) {
            $this->lineWidth = $width;
        }
    }

    public function SetFont(string $family, string $style = '', float $size = 0): void
    {
        $family = trim(strtolower($family));
        if ($family === 'times' || $family === 'times new roman') {
            $family = 'times';
        } else {
            $family = 'times';
        }
        $style = strtoupper(preg_replace('/\s+/', '', $style ?? '') ?? '');

        $underline = false;
        if (str_contains($style, 'U')) {
            $underline = true;
            $style = str_replace('U', '', $style);
        }
        if ($style === '') {
            $style = '';
        }
        $key = $family . '_' . ($style === '' ? 'R' : $style);

        if (!isset($this->fonts[$key])) {
            $this->_registerFont($family, $style, $key);
        }

        $this->currentFontKey = $this->fonts[$key]['i'];
        if ($size > 0) {
            $this->fontSizePt = $size;
        }
        $this->fontUnderline = $underline;

        if ($this->pageNo > 0) {
            $this->pages[$this->pageNo] .= sprintf("BT /F%d %.2f Tf ET\n", $this->currentFontKey, $this->fontSizePt);
            if (!isset($this->pageResources[$this->pageNo]['fonts'][$this->currentFontKey])) {
                $this->pageResources[$this->pageNo]['fonts'][$this->currentFontKey] = true;
            }
        }
    }

    private function _registerFont(string $family, string $style, string $key): void
    {
        static $fontSeq = 0;
        $fontSeq++;

        $bold   = in_array('B', str_split($style), true);
        $italic = in_array('I', str_split($style), true);

        $name = 'Times';
        if ($bold && $italic) {
            $name = 'Times-BoldItalic';
        } elseif ($bold) {
            $name = 'Times-Bold';
        } elseif ($italic) {
            $name = 'Times-Italic';
        } else {
            $name = 'Times-Roman';
        }

        $this->fonts[$key] = [
            'i' => $fontSeq,
            'name' => $name,
            'family' => $family,
            'style' => $style,
        ];
    }

    public function GetStringWidth(string $s): float
    {
        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        $s = $this->_toLatin1($s);
        $l = strlen($s);
        $ptSize = $this->fontSizePt;

        $width = 0;
        for ($i = 0; $i < $l; $i++) {
            $c = ord($s[$i]);
            $width += $this->_charWidthTimes($c);
        }

        $wPt = ($width / 1000) * $ptSize;
        return $wPt / 2.83465;
    }

    private function _charWidthTimes(int $c): float
    {
        if ($c < 32 || $c > 255) {
            return 500;
        }
        $w = [
            32=>250,33=>278,34=>355,35=>556,36=>556,37=>889,38=>667,39=>191,40=>333,41=>333,
            42=>389,43=>584,44=>278,45=>333,46=>278,47=>278,48=>556,49=>556,50=>556,51=>556,
            52=>556,53=>556,54=>556,55=>556,56=>556,57=>556,58=>278,59=>278,60=>584,61=>584,
            62=>584,63=>556,64=>1015,65=>667,66=>667,67=>722,68=>722,69=>667,70=>611,71=>778,
            72=>722,73=>278,74=>500,75=>667,76=>556,77=>833,78=>722,79=>778,80=>667,81=>778,
            82=>722,83=>667,84=>611,85=>722,86=>667,87=>944,88=>667,89=>667,90=>611,91=>278,
            92=>278,93=>278,94=>469,95=>556,96=>333,97=>556,98=>556,99=>500,100=>556,101=>556,
            102=>278,103=>556,104=>556,105=>222,106=>222,107=>500,108=>222,109=>833,110=>556,
            111=>556,112=>556,113=>556,114=>333,115=>500,116=>278,117=>556,118=>500,119=>722,
            120=>500,121=>500,122=>500,123=>334,124=>260,125=>334,126=>584,127=>0,
            128=>500,129=>0,130=>278,131=>0,132=>0,133=>0,134=>0,135=>0,136=>0,137=>0,138=>0,
            139=>0,140=>0,141=>0,142=>0,143=>0,144=>0,145=>0,146=>0,147=>0,148=>0,149=>0,150=>333,
            151=>500,152=>0,153=>0,154=>0,155=>0,156=>0,157=>0,158=>0,159=>0,160=>250,161=>278,
            162=>556,163=>556,164=>0,165=>556,166=>0,167=>556,168=>0,169=>722,170=>278,171=>500,
            172=>556,173=>333,174=>0,175=>0,176=>400,177=>584,178=>333,179=>333,180=>365,181=>556,
            182=>556,183=>278,184=>333,185=>278,186=>333,187=>500,188=>667,189=>667,190=>667,
            191=>556,192=>667,193=>667,194=>667,195=>667,196=>667,197=>667,198=>889,199=>722,
            200=>667,201=>667,202=>667,203=>667,204=>278,205=>278,206=>278,207=>278,208=>722,
            209=>722,210=>778,211=>778,212=>778,213=>778,214=>778,215=>584,216=>778,217=>722,
            218=>722,219=>722,220=>722,221=>667,222=>722,223=>611,224=>556,225=>556,226=>556,
            227=>556,228=>556,229=>556,230=>722,231=>500,232=>556,233=>556,234=>556,235=>556,
            236=>222,237=>222,238=>222,239=>222,240=>556,241=>556,242=>556,243=>556,244=>556,
            245=>556,246=>556,247=>584,248=>556,249=>556,250=>556,251=>556,252=>556,253=>500,
            254=>500,255=>500,
        ];
        return (float)($w[$c] ?? 500);
    }

    private function _toLatin1(string $s): string
    {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if ($converted !== false) {
                return $converted;
            }
        }
        $out = '';
        $arr = @unpack('C*', $s);
        if ($arr === false) {
            return $s;
        }
        $bytes = array_values($arr);
        $len = count($bytes);
        $i = 0;
        while ($i < $len) {
            $c = $bytes[$i];
            if ($c < 0x80) {
                $out .= chr($c);
                $i++;
            } elseif ($c < 0xC0) {
                $out .= '?';
                $i++;
            } elseif ($c < 0xE0) {
                $u = ($c & 0x1F) << 6;
                if ($i + 1 < $len) {
                    $u |= ($bytes[$i + 1] & 0x3F);
                    $i += 2;
                } else {
                    $i++;
                }
                if ($u < 0x100) {
                    $out .= chr($u);
                } else {
                    $out .= '?';
                }
            } else {
                $out .= '?';
                if ($c < 0xF0) {
                    $i += 3;
                } else {
                    $i += 4;
                }
            }
        }
        return $out;
    }

    public function Ln(float $h = 0): void
    {
        $this->x = $this->lMargin;
        if ($h > 0) {
            $this->y += $h;
        } else {
            $this->y += $this->lasth;
        }
    }

    public function Cell(
        float $w,
        float $h = 0,
        string $txt = '',
        int $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false,
        string $link = ''
    ): void {
        if ($h === 0) {
            $h = 5;
        }
        $k = 2.83465;
        $s = '';
        $txt = (string)$txt;

        if ($w === 0) {
            $w = $this->pageWidth - $this->rMargin - $this->x;
        }

        if ($fill || $border > 0) {
            $ptsX = $this->x * $k;
            $ptsY = ($this->pageHeight - $this->y) * $k;
            $ptsW = $w * $k;
            $ptsH = $h * $k;
            $s .= sprintf(
                "%.2f %.2f %.2f %.2f re %s\n",
                $ptsX,
                $ptsY - $ptsH,
                $ptsW,
                $ptsH,
                $fill ? ($border > 0 ? 'B' : 'f') : 'S'
            );
        }

        if ($txt !== '') {
            if ($this->currentFontKey === null) {
                $this->SetFont('Times', '', 12);
            }
            $up = $this->fontUnderline ? 'T* ' : '';

            if (!isset($this->pageResources[$this->pageNo]['fonts'][$this->currentFontKey])) {
                $this->pageResources[$this->pageNo]['fonts'][$this->currentFontKey] = true;
            }

            $txtPrint = $this->_escapePdfText($this->_toLatin1($txt));

            if ($align === 'C') {
                $tx = $this->x + ($w - $this->GetStringWidth($txt)) / 2;
            } elseif ($align === 'R') {
                $tx = $this->x + $w - $this->GetStringWidth($txt);
            } else {
                $tx = $this->x;
            }
            $ty = ($this->pageHeight - ($this->y + $h / 2 + $this->fontSizePt / (2 * $k))) * $k;

            $s .= sprintf(
                "BT /F%d %.2f Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj %sET\n",
                $this->currentFontKey,
                $this->fontSizePt,
                $tx * $k,
                $ty,
                $txtPrint,
                $up
            );
        }

        if ($this->pageNo > 0) {
            $this->pages[$this->pageNo] .= $s;
        }

        $this->lasth = $h;
        if ($ln > 0) {
            if ($ln === 1) {
                $this->x = $this->lMargin;
                $this->y += $h;
                if ($this->autoPageBreak && $this->y > $this->pageBreakTrigger && $this->pageNo > 0) {
                    $this->AddPage();
                }
            } elseif ($ln === 2) {
                $this->y += $h;
            }
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell(
        float $w,
        float $h,
        string $txt,
        int $border = 0,
        string $align = 'L',
        bool $fill = false
    ): void {
        if ($w === 0) {
            $w = $this->pageWidth - $this->rMargin - $this->x;
        }
        $lines = $this->_wrapText($txt, $w);
        foreach ($lines as $i => $line) {
            $ln = ($i === count($lines) - 1) ? 1 : 2;
            $this->Cell($w, $h, $line, $border, 1, $align, $fill);
        }
    }

    private function _wrapText(string $txt, float $w): array
    {
        $txt = str_replace("\r\n", "\n", $txt);
        $txt = str_replace("\r", "\n", $txt);
        $segments = explode("\n", $txt);
        $out = [];
        foreach ($segments as $seg) {
            if ($seg === '') {
                $out[] = '';
                continue;
            }
            $words = preg_split('/(\s+)/u', $seg, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($words === false || $words === []) {
                $out[] = $seg;
                continue;
            }
            $current = '';
            $curWidth = 0;
            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                $wWord = $this->GetStringWidth($word);
                if ($curWidth + $wWord > $w && $current !== '') {
                    $out[] = rtrim($current);
                    $current = ltrim($word);
                    $curWidth = $this->GetStringWidth($current);
                } else {
                    $current .= $word;
                    $curWidth += $wWord;
                }
            }
            if ($current !== '') {
                $out[] = $current;
            }
        }
        return $out;
    }

    private function _escapePdfText(string $s): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $s
        );
    }

    public function Output(string $dest = 'I', string $name = 'doc.pdf', bool $isUTF8 = true): string
    {
        if ($this->pageNo === 0) {
            $this->AddPage();
        }
        $this->_endpage();
        return $this->_dooutput($dest, $name);
    }

    private function _dooutput(string $dest, string $name): string
    {
        $pdf = $this->_getpdfbuffer();
        if ($dest === 'S') {
            return $pdf;
        }

        $name = str_replace(["\r", "\n"], ['_', '_'], $name);

        if ($dest === 'I' || $dest === '') {
            header('Content-Type: application/pdf');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Content-Disposition: inline; filename="' . $name . '"');
            echo $pdf;
            exit;
        } elseif ($dest === 'D') {
            header('Content-Type: application/pdf');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($pdf));
            header('Content-Disposition: attachment; filename="' . $name . '"');
            echo $pdf;
            exit;
        } elseif ($dest === 'F') {
            $f = @fopen($name, 'wb');
            if ($f === false) {
                throw new RuntimeException('Unable to create output file: ' . $name);
            }
            fwrite($f, $pdf);
            fclose($f);
            return '';
        }
        return $pdf;
    }

    private function _getpdfbuffer(): string
    {
        $this->n        = 0;
        $this->offsets  = [];
        $this->buffer   = "%PDF-1.4\n";
        $this->buffer  .= "%âãÏÓ\n";

        $objectPages      = $this->_newobj();
        $objectCatalog    = $this->_newobj();

        $pageObjIds = [];
        for ($i = 1; $i <= $this->pageNo; $i++) {
            $pageObjIds[] = $this->_newobj();
        }

        $fontObjIds = [];
        foreach ($this->fonts as $key => $f) {
            $fontObjIds[$f['i']] = $this->_newobj();
        }

        for ($i = 1; $i <= $this->pageNo; $i++) {
            $this->_putstreamobject($pageObjIds[$i - 1], $this->_getpagecontent($i));
        }

        foreach ($this->fonts as $key => $f) {
            $this->_putfontobject($fontObjIds[$f['i']], $f['name']);
        }

        $resObjMap = [];
        for ($i = 1; $i <= $this->pageNo; $i++) {
            $resId = $this->_newobj();
            $resObjMap[$i] = $resId;
            $this->_putresourcesobject($resId, $this->pageResources[$i], $fontObjIds);
        }

        $kids = '';
        foreach ($pageObjIds as $pid) {
            $kids .= sprintf('%d 0 R ', $pid);
        }
        $this->offsets[$objectPages] = strlen($this->buffer);
        $this->buffer .= sprintf(
            "%d 0 obj\n<< /Type /Pages /Kids [%s] /Count %d >>\nendobj\n",
            $objectPages,
            $kids,
            $this->pageNo
        );

        foreach ($pageObjIds as $idx => $pid) {
            $pageNumber = $idx + 1;
            $resId = $resObjMap[$pageNumber];
            $this->offsets[$pid] = strlen($this->buffer);
            $this->buffer .= sprintf(
                "%d 0 obj\n<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Resources %d 0 R /Contents %d 0 R >>\nendobj\n",
                $pid,
                $objectPages,
                $this->pageWidth * 2.83465,
                $this->pageHeight * 2.83465,
                $resId,
                $pid
            );
        }

        $this->offsets[$objectCatalog] = strlen($this->buffer);
        $this->buffer .= sprintf(
            "%d 0 obj\n<< /Type /Catalog /Pages %d 0 R >>\nendobj\n",
            $objectCatalog,
            $objectPages
        );

        $xrefPos = strlen($this->buffer);
        $this->buffer .= "xref\n";
        $countObj = $this->n + 1;
        $this->buffer .= sprintf("0 %d\n", $countObj);
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $this->n; $i++) {
            $off = $this->offsets[$i] ?? 0;
            $this->buffer .= sprintf("%010d 00000 n \n", $off);
        }

        $this->buffer .= "trailer\n";
        $this->buffer .= sprintf("<< /Size %d /Root %d 0 R >>\n", $countObj, $objectCatalog);
        $this->buffer .= "startxref\n";
        $this->buffer .= $xrefPos . "\n";
        $this->buffer .= "%%EOF\n";

        return $this->buffer;
    }

    private function _getpagecontent(int $page): string
    {
        return "q\n" . ($this->pages[$page] ?? '') . "Q\n";
    }

    private function _newobj(): int
    {
        $this->n++;
        return $this->n;
    }

    private function _putstreamobject(int $objId, string $stream): void
    {
        $this->offsets[$objId] = strlen($this->buffer);
        $this->buffer .= sprintf(
            "%d 0 obj\n<< /Length %d >>\nstream\n%s\nendstream\nendobj\n",
            $objId,
            strlen($stream),
            $stream
        );
    }

    private function _putfontobject(int $objId, string $fontName): void
    {
        $this->offsets[$objId] = strlen($this->buffer);
        $this->buffer .= sprintf(
            "%d 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /%s /Encoding /WinAnsiEncoding >>\nendobj\n",
            $objId,
            $fontName
        );
    }

    private function _putresourcesobject(int $objId, array $pageRes, array $fontObjIds): void
    {
        $fonts = '';
        if (!empty($pageRes['fonts'])) {
            foreach ($pageRes['fonts'] as $fi => $_) {
                if (isset($fontObjIds[$fi])) {
                    $fonts .= sprintf("/F%d %d 0 R ", $fi, $fontObjIds[$fi]);
                }
            }
        }
        $this->offsets[$objId] = strlen($this->buffer);
        $this->buffer .= sprintf(
            "%d 0 obj\n<< /Font << %s >> /ProcSet [/PDF /Text] >>\nendobj\n",
            $objId,
            $fonts
        );
    }
}
