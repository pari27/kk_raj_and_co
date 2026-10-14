<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Writes a single-sheet .xlsx file with no extra libraries. Text is stored as
 * text (so dates never turn into "########"), numbers as numbers, and column
 * widths are sized to their content.
 *
 * A cell is a string, an int/float, a DateTimeInterface (shown as dd-mm-yyyy), or null (empty). Row styles: 'title',
 * 'header', 'total', or null for a normal row. Numbers in the column indexes
 * given as money columns are shown with thousands separators and two decimals.
 * The title row is merged across all columns and centred.
 */
class XlsxWriter
{
    /** @var array<int, array{cells: array<int, mixed>, style: string|null}> */
    private array $rows = [];

    /** @var array<int, bool> */
    private array $moneyColumns = [];

    public function __construct(private readonly string $sheetName = 'Report') {}

    /**
     * @param  array<int, mixed>  $cells
     */
    public function addRow(array $cells, ?string $style = null): void
    {
        $this->rows[] = ['cells' => array_values($cells), 'style' => $style];
    }

    /**
     * @param  array<int, int>  $columns  zero-based column indexes
     */
    public function moneyColumns(array $columns): void
    {
        $this->moneyColumns = array_fill_keys($columns, true);
    }

    /**
     * Build the file and return the path of a temporary .xlsx (caller deletes it).
     */
    public function save(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$this->escape($this->sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->close();

        return $path;
    }

    // Style indexes (see styles()): 0 normal, 1 title, 2 header, 3 money, 4 total, 5 total money, 6 date.
    private function styleFor(?string $rowStyle, bool $isMoney): int
    {
        return match ($rowStyle) {
            'title' => 1,
            'header' => 2,
            'total' => $isMoney ? 5 : 4,
            default => $isMoney ? 3 : 0,
        };
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.00"/><numFmt numFmtId="165" formatCode="dd\-mm\-yyyy"/></numFmts>'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF101B3D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF1F3F7"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9DEE8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="164" fontId="1" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1"/>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="left"/></xf>'
            .'</cellXfs></styleSheet>';
    }

    private function sheet(): string
    {
        $widths = [];

        foreach ($this->rows as $row) {
            // The title/subtitle lines span the sheet; don't let them widen column A.
            if ($row['style'] === 'title') {
                continue;
            }

            foreach ($row['cells'] as $index => $cell) {
                $length = $cell instanceof \DateTimeInterface ? 10 : (is_float($cell) || is_int($cell) ? strlen(number_format((float) $cell, 2)) : mb_strlen((string) $cell));
                $widths[$index] = max($widths[$index] ?? 8, min(60, $length + 3));
            }
        }

        $cols = '';
        foreach ($widths as $index => $width) {
            $cols .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        $data = '';
        foreach ($this->rows as $rowIndex => $row) {
            $data .= '<row r="'.($rowIndex + 1).'">';

            foreach ($row['cells'] as $index => $cell) {
                if ($cell === null || $cell === '') {
                    continue;
                }

                $ref = $this->column($index).($rowIndex + 1);

                if ($cell instanceof \DateTimeInterface) {
                    $data .= '<c r="'.$ref.'" s="6"><v>'.$this->serial($cell).'</v></c>';

                    continue;
                }

                $isNumber = is_int($cell) || is_float($cell);
                $style = $this->styleFor($row['style'], $isNumber && isset($this->moneyColumns[$index]));

                $data .= $isNumber
                    ? '<c r="'.$ref.'" s="'.$style.'"><v>'.$cell.'</v></c>'
                    : '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $cell).'</t></is></c>';
            }

            $data .= '</row>';
        }

        $merge = '';
        $columnCount = count($widths);

        foreach ($this->rows as $rowIndex => $row) {
            if ($row['style'] === 'title' && $columnCount > 1) {
                $merge .= '<mergeCell ref="A'.($rowIndex + 1).':'.$this->column($columnCount - 1).($rowIndex + 1).'"/>';
            }
        }

        $merge = $merge === '' ? '' : '<mergeCells count="'.substr_count($merge, '<mergeCell ').'">'.$merge.'</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols>'.$cols.'</cols><sheetData>'.$data.'</sheetData>'.$merge.'</worksheet>';
    }

    /**
     * Excel stores a date as the number of days since 1899-12-30.
     */
    private function serial(\DateTimeInterface $date): int
    {
        $utc = new \DateTimeImmutable($date->format('Y-m-d').' 00:00:00', new \DateTimeZone('UTC'));

        return intdiv($utc->getTimestamp(), 86400) + 25569;
    }

    private function column(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $index--;
            $name = chr(65 + $index % 26).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        // Drop control characters XML cannot hold, then escape the rest.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
