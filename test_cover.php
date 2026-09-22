<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Copy template to test_template.docx
$src = resource_path('templates/kak_template_merged.docx');
$dst = storage_path('app/test_template.docx');
copy($src, $dst);

$zip = new ZipArchive();
if ($zip->open($dst) !== true) {
    die("Cannot open test_template.docx\n");
}

$xml = $zip->getFromName('word/document.xml');
$dom = new DOMDocument();
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$allP = $xpath->query('//w:body/w:p');

// P#1 (0), P#2 (1), P#3 (2)
// P#7 (6), P#8 (7)
// P#28 (27), P#29 (28)
// P#32 (31) - TAHUN ${field_010}
// P#33 (32), P#34 (33), P#35 (34), P#36 (35)

$nodesToRemove = [];
// Remove P#1, P#2
$nodesToRemove[] = $allP->item(0);
$nodesToRemove[] = $allP->item(1);

// Remove P#8
$nodesToRemove[] = $allP->item(7);

// Remove P#29
$nodesToRemove[] = $allP->item(28);

// Keep P#36 as the page break paragraph, remove P#33, P#34, P#35
// Remove P#33, P#34, P#35
$nodesToRemove[] = $allP->item(32);
$nodesToRemove[] = $allP->item(33);
$nodesToRemove[] = $allP->item(34);
// P#36 is item(35) which already has <w:br w:type="page"/>!


foreach ($nodesToRemove as $n) {
    $n->parentNode->removeChild($n);
}

$newXml = $dom->saveXML();
$zip->deleteName('word/document.xml');
$zip->addFromString('word/document.xml', $newXml);
$zip->close();

$zip = new ZipArchive();
if ($zip->open($dst) === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $allP = $xpath->query('//w:body/w:p');
    echo "Total paragraphs after update: " . $allP->length . "\n";
    \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
    $sub = App\Models\KakSubmission::find(24);
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($dst);
    $fieldMap = json_decode(file_get_contents(resource_path('templates/field_map.json')), true);
    foreach ($fieldMap as $item) {
        $key = $item['key'];
        $val = $sub->data[$key] ?? '-';
        $templateProcessor->setValue($key, (string)$val);
    }
    $genDocx = storage_path('app/output/test_sub24_cleaned.docx');
    $templateProcessor->saveAs($genDocx);
    echo "Saved genDocx: {$genDocx}, size=" . filesize($genDocx) . "\n";

    $generator = app(App\Services\KakDocumentGenerator::class);
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('updateWaktuTableXml');
    $method->setAccessible(true);
    echo "Testing PDF generation from generated docx...\n";
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($genDocx);
    $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
    $html = $htmlWriter->getContent();

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfPath = storage_path('app/output/test_sub24_cleaned.pdf');
    file_put_contents($pdfPath, $dompdf->output());
    echo "Saved PDF: {$pdfPath}, size=" . filesize($pdfPath) . "\n";
}




