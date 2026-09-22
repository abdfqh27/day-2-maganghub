<?php

$zip = new ZipArchive();
if ($zip->open(__DIR__ . '/resources/templates/kak_template_merged.docx') === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $allBreaks = $xpath->query('//w:br[@w:type="page"]');
    echo "Total explicit page breaks: " . $allBreaks->length . "\n";
    foreach ($allBreaks as $i => $br) {
        $parentP = $br->parentNode;
        while ($parentP && $parentP->nodeName !== 'w:p') {
            $parentP = $parentP->parentNode;
        }
        $prevP = $parentP ? $parentP->previousSibling : null;
        while ($prevP && $prevP->nodeName !== 'w:p') {
            $prevP = $prevP->previousSibling;
        }
        $nextP = $parentP ? $parentP->nextSibling : null;
        while ($nextP && $nextP->nodeName !== 'w:p') {
            $nextP = $nextP->nextSibling;
        }

        echo "Break #{$i}:\n";
        echo "   In P text: " . ($parentP ? trim($parentP->textContent) : '') . "\n";
        echo "   Prev P text: " . ($prevP ? substr(trim($prevP->textContent), 0, 60) : 'none') . "\n";
        echo "   Next P text: " . ($nextP ? substr(trim($nextP->textContent), 0, 60) : 'none') . "\n";
    }

    $sects = $xpath->query('//w:sectPr');
    echo "\nTotal sectPr: " . $sects->length . "\n";
    foreach ($sects as $i => $s) {
        $parent = $s->parentNode;
        echo "SectPr #{$i} in: " . $parent->nodeName . " text: " . substr(trim($parent->textContent), 0, 60) . "\n";
    }
}
