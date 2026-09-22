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

    for ($i = 15; $i <= 55; $i++) {
        if ($i < $paragraphs->length) {
            $p = $paragraphs->item($i);
            $pIdx = $i + 1;
            $text = trim($p->textContent);
            $hasPb = $xpath->query('.//w:br[@w:type="page"]', $p)->length;
            $hasSect = $xpath->query('.//w:sectPr', $p)->length;
            $xmlSnippet = $dom->saveXML($p);
            echo "P#{$pIdx} (len=" . strlen($text) . ") [pb={$hasPb}, sect={$hasSect}]: " . substr($text, 0, 70) . "\n";
            if (strlen($text) === 0 || $hasPb) {
                echo "   XML: " . substr($xmlSnippet, 0, 150) . "\n";
            }
        }
    }
}
