<?php
if (class_exists('COM')) {
    echo "COM class exists!\n";
    try {
        $word = new COM("Word.Application");
        echo "Word version: " . $word->Version . "\n";
        $word->Quit();
    } catch (\Throwable $e) {
        echo "Word COM error: " . $e->getMessage() . "\n";
    }
} else {
    echo "COM extension is not enabled in PHP.\n";
}
