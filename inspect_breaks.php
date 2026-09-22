<?php

$zip = new ZipArchive();
if ($zip->open(__DIR__ . '/resources/templates/kak_template_merged.docx') === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    // Find all paragraphs in order
    $paragraphs = $xpath->query('//w:body/w:p');
    echo "Total paragraphs: " . $paragraphs->length . "\n";

    $pIndex = 0;
    foreach ($paragraphs as $p) {
        $pIndex++;
        $text = trim($p->textContent);
        $hasPageBreak = $xpath->query('.//w:br[@w:type="page"]', $p)->length > 0;
        $hasSectPr = $xpath->query('w:pPr/w:sectPr', $p)->length > 0;
        $hasLastRenderedPageBreak = $xpath->query('.//w:lastRenderedPageBreak', $p)->length > 0;

        if ($hasPageBreak || $hasSectPr || $hasLastRenderedPageBreak || $pIndex <= 15) {
            echo "P#{$pIndex} [len=" . strlen($text) . "] ";
            if ($hasPageBreak) echo "[PAGE_BREAK] ";
            if ($hasSectPr) echo "[SECT_PR] ";
            if ($hasLastRenderedPageBreak) echo "[LAST_RENDERED_BREAK] ";
            echo substr($text, 0, 80) . "\n";
        }
    }
}
