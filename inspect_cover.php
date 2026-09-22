<?php

$zip = new ZipArchive();
if ($zip->open(__DIR__ . '/resources/templates/kak_template_merged.docx') === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $paragraphs = $xpath->query('//w:body/w:p');

    for ($i = 0; $i < 37; $i++) {
        $p = $paragraphs->item($i);
        $pIdx = $i + 1;
        $text = trim($p->textContent);
        $spacingNode = $xpath->query('w:pPr/w:spacing', $p)->item(0);
        $spacing = '';
        if ($spacingNode) {
            foreach ($spacingNode->attributes as $attr) {
                $spacing .= "{$attr->name}={$attr->value} ";
            }
        }
        $rPr = $xpath->query('.//w:rPr', $p)->item(0);
        $fontSize = $xpath->query('.//w:sz', $p)->item(0);
        $szVal = $fontSize ? $fontSize->getAttribute('w:val') : '';
        echo "P#{$pIdx} [sz={$szVal}] [spacing: {$spacing}]: " . ($text ?: '(EMPTY)') . "\n";
    }
}
