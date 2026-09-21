<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SpreadsheetReader
{
    private const MAX_UNCOMPRESSED_BYTES = 52428800;
    private const MAX_ZIP_ENTRIES = 500;

    public function read(string $path, string $extension): array
    {
        $extension = strtolower(ltrim($extension, '.'));
        return match ($extension) {
            'csv' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw new RuntimeException('Only CSV and XLSX files are supported.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) throw new RuntimeException('Unable to open CSV file.');
        try {
            $first = fgets($handle);
            if ($first === false) return [];
            $delimiter = $this->detectDelimiter($first);
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            if (! $headers) return [];
            $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headers);
            $rows = [];
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->emptyRow($values)) continue;
                $values = array_pad($values, count($headers), null);
                $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
            }
            return $rows;
        } finally { fclose($handle); }
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) throw new RuntimeException('PHP zip extension is required for XLSX imports.');
        $magic = file_get_contents($path, false, null, 0, 2);
        if ($magic !== 'PK') throw new RuntimeException('Invalid XLSX file signature.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Unable to open XLSX archive.');
        try {
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) throw new RuntimeException('XLSX archive has too many entries.');
            $size = 0;
            for ($i=0; $i<$zip->numFiles; $i++) { $stat=$zip->statIndex($i); $size += (int)($stat['size']??0); if($size>self::MAX_UNCOMPRESSED_BYTES) throw new RuntimeException('XLSX archive is too large when expanded.'); }
            $shared = $this->sharedStrings($zip);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheet === false) throw new RuntimeException('XLSX first worksheet is missing.');
            $xml = simplexml_load_string($sheet, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if (! $xml) throw new RuntimeException('Invalid XLSX worksheet XML.');
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $xml->registerXPathNamespace('a', $ns);
            $matrix = [];
            foreach ($xml->xpath('//a:sheetData/a:row') ?: [] as $row) {
                $cells = [];
                foreach ($row->xpath('a:c') ?: [] as $cell) {
                    $ref = (string) $cell['r'];
                    preg_match('/^([A-Z]+)/', $ref, $m);
                    $index = $this->columnIndex($m[1] ?? 'A');
                    $type = (string) $cell['t'];
                    $children = $cell->children($ns);
                    $value = (string) ($children->v ?? '');
                    if ($type === 's') $value = $shared[(int)$value] ?? '';
                    elseif ($type === 'inlineStr') { $cell->registerXPathNamespace('a',$ns); $parts=$cell->xpath('.//a:t')?:[]; $value=implode('',array_map(fn($p)=>(string)$p,$parts)); }
                    elseif ($type === 'b') $value = $value === '1' ? '1' : '0';
                    $cells[$index] = $value;
                }
                if ($cells === []) continue;
                $max = max(array_keys($cells)); $line=[]; for($i=0;$i<=$max;$i++)$line[]=$cells[$i]??''; $matrix[]=$line;
            }
            if ($matrix === []) return [];
            $headers = array_map(fn($h)=>$this->normalizeHeader((string)$h), array_shift($matrix));
            $rows=[]; foreach($matrix as $line){if($this->emptyRow($line))continue;$line=array_pad($line,count($headers),null);$rows[]=array_combine($headers,array_slice($line,0,count($headers)));}
            return $rows;
        } finally { $zip->close(); }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) return [];
        $xml = simplexml_load_string($content, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if (! $xml) return [];
        $ns='http://schemas.openxmlformats.org/spreadsheetml/2006/main'; $xml->registerXPathNamespace('a',$ns); $result=[];
        foreach($xml->xpath('//a:si')?:[] as $si){$si->registerXPathNamespace('a',$ns);$parts=$si->xpath('.//a:t')?:[];$result[]=implode('',array_map(fn($p)=>(string)$p,$parts));}
        return $result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header));
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($header)), '_');
    }
    private function detectDelimiter(string $line): string { $counts=[','=>substr_count($line,','),';'=>substr_count($line,';'),"\t"=>substr_count($line,"\t")]; arsort($counts); return (string)array_key_first($counts); }
    private function emptyRow(array $row): bool { foreach($row as $value) if(trim((string)$value)!=='') return false; return true; }
    private function columnIndex(string $letters): int { $n=0; foreach(str_split($letters) as $c)$n=$n*26+(ord($c)-64); return $n-1; }
}
