<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sub = App\Models\KakSubmission::find(24);
$generator = app(App\Services\KakDocumentGenerator::class);
$genDocx = storage_path('app/output/test_sub24_cleaned.docx');

$reflection = new ReflectionClass($generator);
$method = $reflection->getMethod('updateWaktuTableXml');
$method->setAccessible(true);
$method->invoke($generator, $genDocx, $sub->data['tabel_waktu'] ?? [], $sub->data ?? []);

$phpWord = \PhpOffice\PhpWord\IOFactory::load($genDocx);
$htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
$html = $htmlWriter->getContent();

$chkPos = strpos($html, '✓');
echo "Checkmark symbol position in HTML: " . ($chkPos !== false ? $chkPos : 'NOT FOUND') . "\n";
if ($chkPos !== false) {
    echo "Checkmark context:\n" . substr($html, $chkPos - 50, 100) . "\n";
}

// Also check Dompdf rendering with DejaVu Sans (which supports Unicode checkmark ✓)
$options = new \Dompdf\Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
echo "Render finished without error! PDF size: " . strlen($dompdf->output()) . "\n";
