<?php
/**
 * Test generate untuk semua submission yang ada,
 * khususnya yang berisi karakter XML khusus (& < >).
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$submissions = App\Models\KakSubmission::orderBy('id')->get();
$generator = app(App\Services\KakDocumentGenerator::class);

$passed = 0;
$failed = 0;

foreach ($submissions as $sub) {
    // Find if any field has XML-dangerous characters
    $hasSpecialChars = false;
    foreach ($sub->data ?? [] as $k => $v) {
        if (is_string($v) && (str_contains($v, '&') || str_contains($v, '<') || str_contains($v, '>'))) {
            $hasSpecialChars = true;
            break;
        }
    }

    try {
        $res = $generator->generate($sub, 'docx');
        $size = file_exists(storage_path('app/' . $res)) ? filesize(storage_path('app/' . $res)) : 0;
        $flag = $hasSpecialChars ? ' [HAS &<>]' : '';
        echo "✅ Sub #{$sub->id}{$flag}: {$res} ({$size} bytes)\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "❌ Sub #{$sub->id}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n--- HASIL: {$passed} berhasil, {$failed} gagal ---\n";
