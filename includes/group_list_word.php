<?php

declare(strict_types=1);

require_once __DIR__ . '/students.php';

function group_list_word_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function group_list_word_cell(string $text, string $widthTwips, bool $bold = false, string $align = 'center'): string
{
    $boldXml = $bold ? '<w:b/>' : '';
    $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';

    return '<w:tc>'
        . '<w:tcPr><w:tcW w:w="' . $widthTwips . '" w:type="dxa"/><w:tcBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '</w:tcBorders><w:vAlign w:val="center"/></w:tcPr>'
        . '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr>'
        . '<w:r><w:rPr>' . $boldXml . '<w:sz w:val="22"/></w:rPr>'
        . '<w:t xml:space="preserve">' . group_list_word_xml_escape($text) . '</w:t>'
        . '</w:r></w:p></w:tc>';
}

/**
 * @param array<int, array<string, mixed>> $students
 */
function build_group_list_docx(array $group, array $students): string
{
    $groupNumber = (string) ($group['number'] ?? '');
    $rowsXml = '';
    $rowsXml .= '<w:tr>'
        . group_list_word_cell('№', '800', true)
        . group_list_word_cell('Студент', '5500', true, 'left')
        . group_list_word_cell('', '2500', true)
        . '</w:tr>';

    foreach ($students as $index => $student) {
        $name = trim((string) ($student['full_name'] ?? ''));
        $rowsXml .= '<w:tr>'
            . group_list_word_cell((string) ($index + 1), '800')
            . group_list_word_cell($name, '5500', false, 'left')
            . group_list_word_cell('', '2500')
            . '</w:tr>';
    }

    $title = 'Список группы ' . $groupNumber;
    $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:body>'
        . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
        . '<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr>'
        . '<w:t>' . group_list_word_xml_escape($title) . '</w:t></w:r></w:p>'
        . '<w:p><w:r><w:t></w:t></w:r></w:p>'
        . '<w:tbl>'
        . '<w:tblPr>'
        . '<w:tblW w:w="8800" w:type="dxa"/>'
        . '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '</w:tblBorders>'
        . '</w:tblPr>'
        . '<w:tblGrid>'
        . '<w:gridCol w:w="800"/><w:gridCol w:w="5500"/><w:gridCol w:w="2500"/>'
        . '</w:tblGrid>'
        . $rowsXml
        . '</w:tbl>'
        . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
        . '<w:pgMar w:top="1134" w:right="850" w:bottom="1134" w:left="1134"/>'
        . '</w:sectPr>'
        . '</w:body></w:document>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '</Types>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';

    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';

    $tmp = tempnam(sys_get_temp_dir(), 'group_docx_');
    if ($tmp === false) {
        throw new RuntimeException('Не удалось создать временный файл.');
    }

    $zipPath = $tmp . '.docx';
    @unlink($tmp);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Не удалось создать DOCX-файл.');
    }

    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('word/_rels/document.xml.rels', $docRels);
    $zip->close();

    $binary = file_get_contents($zipPath);
    @unlink($zipPath);

    if ($binary === false || $binary === '') {
        throw new RuntimeException('Не удалось прочитать DOCX-файл.');
    }

    return $binary;
}

function download_group_list_docx(array $group, array $students): void
{
    $groupNumber = preg_replace('/[^\w\-а-яА-ЯёЁ]+/u', '_', (string) ($group['number'] ?? 'group')) ?: 'group';
    $filename = 'spisok_gruppy_' . $groupNumber . '.docx';
    $binary = build_group_list_docx($group, $students);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $binary;
    exit;
}
