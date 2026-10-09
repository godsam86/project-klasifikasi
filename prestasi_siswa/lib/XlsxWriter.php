<?php
/**
 * Penulis file .xlsx minimal & mandiri (tanpa Composer/PhpSpreadsheet).
 * Mendukung banyak sheet, header tebal, dan tipe data teks/angka.
 * Cukup untuk kebutuhan export laporan aplikasi ini.
 */
class XlsxWriter
{
    private array $sheets = []; // nama => rows (array of array)

    public function tambahSheet(string $nama, array $header, array $rows): void
    {
        $nama = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $nama), 0, 31);
        $this->sheets[] = ['nama' => $nama ?: 'Sheet', 'header' => $header, 'rows' => $rows];
    }

    private function colLetter(int $n): string
    {
        $s = '';
        $n++;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    private function cellXml(int $rowIdx, int $colIdx, $value, bool $bold = false): string
    {
        $ref = $this->colLetter($colIdx) . $rowIdx;
        $styleAttr = $bold ? ' s="1"' : '';
        if ($value === null || $value === '') {
            return "<c r=\"$ref\"$styleAttr/>";
        }
        if (is_numeric($value) && !is_string($value)) {
            return "<c r=\"$ref\"$styleAttr><v>" . htmlspecialchars((string)$value) . "</v></c>";
        }
        // Perlakukan angka bertipe string numerik murni sebagai angka juga (kecuali diawali 0 berlebih)
        if (is_numeric($value) && !preg_match('/^0[0-9]/', (string)$value)) {
            return "<c r=\"$ref\"$styleAttr><v>" . htmlspecialchars((string)$value) . "</v></c>";
        }
        $text = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        return "<c r=\"$ref\" t=\"inlineStr\"$styleAttr><is><t xml:space=\"preserve\">$text</t></is></c>";
    }

    private function sheetXml(array $sheet): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        $rowIdx = 1;
        // Header
        $xml .= "<row r=\"$rowIdx\">";
        foreach (array_values($sheet['header']) as $c => $val) {
            $xml .= $this->cellXml($rowIdx, $c, $val, true);
        }
        $xml .= '</row>';
        $rowIdx++;

        foreach ($sheet['rows'] as $row) {
            $xml .= "<row r=\"$rowIdx\">";
            $c = 0;
            foreach ($row as $val) {
                $xml .= $this->cellXml($rowIdx, $c, $val, false);
                $c++;
            }
            $xml .= '</row>';
            $rowIdx++;
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    public function simpan(string $path): void
    {
        if (file_exists($path)) @unlink($path);
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            implode('', array_map(fn($i) => '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', array_keys($this->sheets))) .
            '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');

        $sheetRels = '';
        $sheetEntries = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $sheetRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $sheetEntries .= '<sheet name="' . htmlspecialchars($sheet['nama'], ENT_QUOTES, 'UTF-8') . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }
        $sheetRels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $sheetRels . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' . $sheetEntries . '</sheets></workbook>');

        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2"><font><sz val="10"/><name val="Calibri"/></font>' .
            '<font><sz val="10"/><name val="Calibri"/><b/></font></fonts>' .
            '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0"/></cellStyleXfs>' .
            '<cellXfs count="2"><xf numFmtId="0" fontId="0" xfId="0"/><xf numFmtId="0" fontId="1" xfId="0" applyFont="1"/></cellXfs>' .
            '</styleSheet>');

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet));
        }

        $zip->close();
    }

    /** Helper cepat: satu sheet saja, langsung kirim ke browser sebagai download. */
    public static function download(string $filename, string $sheetName, array $header, array $rows): void
    {
        $writer = new self();
        $writer->tambahSheet($sheetName, $header, $rows);
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->simpan($tmp);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    /** Kirim workbook (multi-sheet) yang sudah dibangun sebagai download. */
    public function kirimDownload(string $filename): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->simpan($tmp);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }
}
