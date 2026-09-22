<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sub = App\Models\KakSubmission::find(24);
foreach ($sub->data as $k => $v) {
    if (is_string($v) && (str_contains($v, '&') || str_contains($v, '<') || str_contains($v, '>'))) {
        echo "Field {$k}: {$v}\n";
    }
}
