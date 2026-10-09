<?php
/**
 * Pembaca file .xlsx minimal & mandiri (tanpa Composer/PhpSpreadsheet).
 * Membaca sheet pertama saja: cukup untuk kebutuhan import data siswa.
 * Mengembalikan array baris (array of array), baris pertama = header.
 */
class XlsxReader
{
    public static function baca(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File .xlsx tidak dapat dibuka (rusak atau bukan format xlsx yang valid).');
        }

        // 1. Baca shared strings (jika ada)
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = simplexml_load_string($sharedXml);
            if ($sx !== false) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string)$si->t;
                    } else {
                        // rich text (banyak <r><t>) - gabungkan
                        $text = '';
                        foreach ($si->r as $r) $text .= (string)$r->t;
                        $shared[] = $text;
                    }
                }
            }
        }

        // 2. Cari sheet pertama lewat workbook.xml + rels (agar urutan sheet benar)
        $sheetPath = 'xl/worksheets/sheet1.xml';
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbXml !== false && $relsXml !== false) {
            $wb = simplexml_load_string($wbXml);
            $rels = simplexml_load_string($relsXml);
            if ($wb !== false && $rels !== false && isset($wb->sheets->sheet[0])) {
                $rId = (string)$wb->sheets->sheet[0]->attributes('r', true)['id'];
                foreach ($rels->Relationship as $rel) {
                    if ((string)$rel['Id'] === $rId) {
                        $sheetPath = self::resolveZipPath('xl/workbook.xml', (string)$rel['Target']);
                        break;
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $entry)) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Sheet data tidak ditemukan di dalam file xlsx.');
        }
        $zip->close();

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            throw new RuntimeException('Gagal membaca struktur sheet xlsx.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            $maxCol = 0;
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/([A-Z]+)(\d+)/', $ref, $m);
                $colIdx = self::colToIndex($m[1] ?? 'A');
                $maxCol = max($maxCol, $colIdx);
                $type = (string)$c['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $value = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string)($c->is->t ?? '');
                } else {
                    $value = (string)$c->v;
                }
                $cells[$colIdx] = $value;
            }
            for ($i = 0; $i <= $maxCol; $i++) {
                $rowData[] = $cells[$i] ?? '';
            }
            $rows[] = $rowData;
        }
        return $rows;
    }

    private static function colToIndex(string $col): int
    {
        $idx = 0;
        foreach (str_split($col) as $ch) {
            $idx = $idx * 26 + (ord($ch) - 64);
        }
        return $idx - 1;
    }

    private static function resolveZipPath(string $baseFile, string $target): string
    {
        $target = str_replace('\\', '/', $target);
        if (str_starts_with($target, '/')) return ltrim($target, '/');

        $parts = explode('/', dirname($baseFile) . '/' . $target);
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') { array_pop($resolved); continue; }
            $resolved[] = $part;
        }
        return implode('/', $resolved);
    }

    /** Ubah hasil baca (array baris) menjadi [header[], rows(assoc)[]] */
    public static function keAsosiatif(array $allRows): array
    {
        if (empty($allRows)) return [[], []];
        $headerIndex = 0;
        $headerScore = 0;
        foreach ($allRows as $index => $candidate) {
            $joined = self::normalizeHeaderText($candidate);
            $score = 0;
            if (preg_match('/\b(nama|name|siswa|student)\b/', $joined)) $score++;
            if (preg_match('/\b(kehadiran|attendance|presence)\b/', $joined)) $score++;
            if (preg_match('/\b(kedisiplinan|discipline)\b/', $joined)) $score++;
            if ($score > $headerScore) {
                $headerIndex = $index;
                $headerScore = $score;
            }
        }
        $header = array_map(static function ($value): string {
            $value = trim((string)$value);
            return preg_replace('/^\xEF\xBB\xBF/', '', $value);
        }, $allRows[$headerIndex]);
        $rows = [];
        for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
            $baris = [];
            foreach ($header as $j => $h) {
                if ($h === '') continue;
                $baris[$h] = $allRows[$i][$j] ?? '';
            }
            // lewati baris yang seluruhnya kosong
            if (count(array_filter($baris, fn($v) => trim((string)$v) !== '')) === 0) continue;
            $rows[] = $baris;
        }
        return [$header, $rows];
    }

    private static function normalizeHeaderText(array $values): string
    {
        $text = implode(' ', array_map('trim', $values));
        $text = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
        return preg_replace('/\s+/', ' ', $text);
    }
}
