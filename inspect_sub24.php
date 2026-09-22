<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sub = App\Models\KakSubmission::find(24);
if ($sub) {
    echo "ID: " . $sub->id . "\n";
    echo "Judul: " . $sub->judul . "\n";
    echo "Format: " . $sub->output_format . "\n";
    echo "Status: " . $sub->status . "\n";
    echo "Path: " . ($sub->generated_file_path ?? 'NULL') . "\n";
    echo "Data count: " . count($sub->data ?? []) . "\n";
    if ($sub->generated_file_path) {
        echo "File exists: " . (file_exists(storage_path('app/' . $sub->generated_file_path)) ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "Submission 24 not found.\n";
    $latest = App\Models\KakSubmission::latest()->take(5)->get();
    foreach ($latest as $l) {
        echo "#{$l->id} - {$l->judul} - {$l->output_format} - Path: " . ($l->generated_file_path ?? 'NULL') . "\n";
    }
}
