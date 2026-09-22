<?php

namespace App\Services;

use App\Models\KakSubmission;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

class KakDocumentGenerator
{
    /**
     * Generate KAK document (.docx or .pdf) based on submission data and requested format.
     *
     * @param KakSubmission $submission
     * @param string $format 'docx' or 'pdf'
     * @return string The relative path to generated file in storage/app/
     * @throws RuntimeException
     */
    public function generate(KakSubmission $submission, string $format = 'docx'): string
    {
        $format = strtolower($format);
        if (!in_array($format, ['docx', 'pdf'])) {
            throw new RuntimeException("Format tidak didukung: {$format}. Pilih 'docx' atau 'pdf'.");
        }

        // 1. Locate template
        $templatePath = resource_path('templates/kak_template_merged.docx');
        if (!file_exists($templatePath)) {
            $templatePath = storage_path('app/templates/kak_template_merged.docx');
        }

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template KAK tidak ditemukan. Harap jalankan 'php artisan kak:prepare-template' terlebih dahulu.");
        }

        // 2. Prepare output directory
        $outputDir = storage_path('app/output');
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        $id = $submission->id ?: 'temp_' . uniqid();
        $docxFileName = "kak_{$id}.docx";
        $pdfFileName = "kak_{$id}.pdf";
        $docxFullPath = $outputDir . DIRECTORY_SEPARATOR . $docxFileName;
        $pdfFullPath = $outputDir . DIRECTORY_SEPARATOR . $pdfFileName;
        $previousFilePath = $submission->generated_file_path ? storage_path('app/' . $submission->generated_file_path) : null;

        // 3. Process DOCX with TemplateProcessor
        //
        // PERBAIKAN (a): Aktifkan XML output escaping di PhpWord SEBELUM TemplateProcessor
        // dibuat. Tanpa ini, karakter & < > " ' dari input pengguna langsung dimasukkan
        // mentah ke dalam word/document.xml sehingga XML menjadi tidak valid dan
        // DOMDocument::loadXML() melempar error "xmlParseEntityRef: no name in Entity".
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $templateProcessor = new TemplateProcessor($templatePath);
        $variables = $templateProcessor->getVariables();
        $data = $submission->data ?? [];

        // Load field map to get all defined fields even if not detected by getVariables()
        $fieldMapPath = resource_path('templates/field_map.json');
        $fieldMapKeys = [];
        if (file_exists($fieldMapPath)) {
            $fieldMap = json_decode(file_get_contents($fieldMapPath), true) ?: [];
            foreach ($fieldMap as $item) {
                $fieldMapKeys[] = $item['key'];
            }
        }

        $allKeys = array_unique(array_merge($variables, $fieldMapKeys));

        foreach ($allKeys as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                continue;
            }
            if ($value === null || trim((string)$value) === '') {
                $displayValue = '-';
            } else {
                $displayValue = (string)$value;
            }

            // setValue() kini aman untuk & < > karena escaping sudah aktif di atas.
            $templateProcessor->setValue($key, $displayValue);
        }

        // Save generated DOCX
        $templateProcessor->saveAs($docxFullPath);

        // Update WAKTU PENCAPAIAN KELUARAN matrix table with checklist and green shading
        $this->updateWaktuTableXml($docxFullPath, $data['tabel_waktu'] ?? [], $data);

        // 4. Handle output format
        if ($format === 'docx') {
            $relativeDocxPath = 'output/' . $docxFileName;
            // Clean up previous file if different (e.g. was pdf)
            if ($previousFilePath && realpath($previousFilePath) !== realpath($docxFullPath) && file_exists($previousFilePath)) {
                @unlink($previousFilePath);
            }

            $submission->output_format = 'docx';
            $submission->generated_file_path = $relativeDocxPath;
            $submission->status = 'final';
            $submission->save();

            return $relativeDocxPath;
        }

        // Format is 'pdf': convert via LibreOffice soffice headless
        $sofficeBinary = $this->resolveSofficeBinary();

        $process = new Process([
            $sofficeBinary,
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $docxFullPath,
        ]);

        $process->setTimeout(120);

        try {
            $process->run();
        } catch (\Exception $e) {
            // Keep docx if error happens for troubleshooting
            Log::error("LibreOffice execution error: " . $e->getMessage());
            throw new RuntimeException(
                "Gagal menjalankan LibreOffice untuk konversi PDF: " . $e->getMessage() . 
                ". Pastikan LibreOffice terpasang di sistem atau gunakan opsi unduh Word (.docx)."
            );
        }

        if (!$process->isSuccessful() || !file_exists($pdfFullPath)) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            Log::error("LibreOffice PDF conversion failed: {$errorOutput}");
            throw new RuntimeException(
                "Konversi ke PDF gagal. Pesan error: " . ($errorOutput ?: 'File PDF tidak terbentuk.') . 
                ". Pastikan LibreOffice (soffice) terpasang di environment atau unduh sebagai Word (.docx)."
            );
        }

        // Delete temporary DOCX since user requested PDF only (as required by specifications)
        if (file_exists($docxFullPath)) {
            @unlink($docxFullPath);
        }

        // Clean up previous file if different (e.g. was docx)
        if ($previousFilePath && realpath($previousFilePath) !== realpath($pdfFullPath) && file_exists($previousFilePath)) {
            @unlink($previousFilePath);
        }

        $relativePdfPath = 'output/' . $pdfFileName;
        $submission->output_format = 'pdf';
        $submission->generated_file_path = $relativePdfPath;
        $submission->status = 'final';
        $submission->save();

        return $relativePdfPath;
    }

    /**
     * Resolve the soffice executable binary for current operating system.
     */
    protected function resolveSofficeBinary(): string
    {
        $configured = env('SOFFICE_BINARY', config('app.soffice_binary', 'soffice'));

        // Check Windows common paths if on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $candidates = [
                $configured,
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return $configured;
    }

    /**
     * Update the Waktu Pencapaian Keluaran matrix table in the DOCX XML with user's checked months and green shading.
     */
    protected function updateWaktuTableXml(string $docxPath, array $tabelWaktu, array $allData = []): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return false;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!$xml) {
            $zip->close();
            return false;
        }

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // PERBAIKAN (b): Tangkap error libxml secara internal sehingga PHP tidak melempar
        // ErrorException. Jika parsing gagal, catat detail baris bermasalah ke log
        // dan kembalikan false — alur generate tetap berjalan, hanya tabel jadwal
        // tidak diperbarui (bukan fatal error).
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        if (!$loaded) {
            $errors = libxml_get_errors();
            foreach ($errors as $err) {
                Log::error('updateWaktuTableXml: DOMDocument parse error', [
                    'message' => trim($err->message),
                    'line'    => $err->line,
                    'column'  => $err->column,
                    'snippet' => mb_substr($xml, max(0, ($err->line - 1) * 80), 160),
                ]);
            }
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            $zip->close();
            return false;
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $body = $xpath->query('//w:body')->item(0);
        if (!$body) {
            $zip->close();
            return false;
        }

        $table = null;
        $foundHeading = false;

        foreach ($body->childNodes as $node) {
            if ($node->nodeName === 'w:p' && stripos($node->textContent, 'WAKTU PENCAPAIAN KELUARAN') !== false) {
                $foundHeading = true;
            }
            if ($foundHeading && $node->nodeName === 'w:tbl') {
                $table = $node;
                break;
            }
        }

        if (!$table) {
            $zip->close();
            return false;
        }

        $rows = $xpath->query('.//w:tr', $table);
        if ($rows->length < 8) {
            $zip->close();
            return false;
        }

        // Row 2: RAK Title
        // PERBAIKAN (c): Gunakan createTextNode() — DOM otomatis meng-escape & < > sehingga
        // XML tetap valid meskipun nilai mengandung ampersand atau tanda kutip.
        $rakTitle = (string)($tabelWaktu['rak_title'] ?? $allData['field_020'] ?? '');
        if ($rakTitle !== '') {
            $r2 = $rows->item(2);
            $c1 = $xpath->query('.//w:tc', $r2)->item(1);
            if ($c1) {
                $tNode = $xpath->query('.//w:t', $c1)->item(0);
                if ($tNode) {
                    // Hapus child nodes lama lalu sisipkan text node baru (auto-escaped)
                    while ($tNode->firstChild) {
                        $tNode->removeChild($tNode->firstChild);
                    }
                    $tNode->appendChild($dom->createTextNode('RAK: ' . $rakTitle));
                }
            }
        }

        // Row 3: Subkomponen 1 Title
        $subkomponen1 = (string)($tabelWaktu['subkomponen_1'] ?? $allData['field_018'] ?? 'Subkomponen 1');
        if ($subkomponen1 !== '') {
            $r3 = $rows->item(3);
            $c1 = $xpath->query('.//w:tc', $r3)->item(1);
            if ($c1) {
                $tNode = $xpath->query('.//w:t', $c1)->item(0);
                if ($tNode) {
                    while ($tNode->firstChild) {
                        $tNode->removeChild($tNode->firstChild);
                    }
                    $tNode->appendChild($dom->createTextNode('Subkomponen: ' . $subkomponen1));
                }
            }
        }

        // Mapping rows to activities (defaults according to KAK template standard)
        $rowActivityMap = [
            4 => $tabelWaktu['kegiatan_1'] ?? [1, 2, 3],
            5 => $tabelWaktu['kegiatan_2'] ?? [2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            6 => $tabelWaktu['kegiatan_3'] ?? [4, 7, 10],
            7 => $tabelWaktu['kegiatan_4'] ?? [11, 12],
        ];

        // Subkomponen 2 (if present in template)
        if ($rows->length >= 13) {
            $subkomponen2 = (string)($tabelWaktu['subkomponen_2'] ?? '');
            if ($subkomponen2 !== '') {
                $r8 = $rows->item(8);
                $c1 = $xpath->query('.//w:tc', $r8)->item(1);
                if ($c1) {
                    $tNode = $xpath->query('.//w:t', $c1)->item(0);
                    if ($tNode) {
                        // PERBAIKAN (c): createTextNode() otomatis escape & < >
                        while ($tNode->firstChild) {
                            $tNode->removeChild($tNode->firstChild);
                        }
                        $tNode->appendChild($dom->createTextNode('Subkomponen: ' . $subkomponen2));
                    }
                }
            }

            if (!empty($tabelWaktu['has_subkomponen_2'])) {
                $rowActivityMap[9] = $tabelWaktu['sub2_kegiatan_1'] ?? [1, 2, 3];
                $rowActivityMap[10] = $tabelWaktu['sub2_kegiatan_2'] ?? [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
                $rowActivityMap[11] = $tabelWaktu['sub2_kegiatan_3'] ?? [4, 7, 10];
                $rowActivityMap[12] = $tabelWaktu['sub2_kegiatan_4'] ?? [11, 12];
            } else {
                $rowActivityMap[9] = [];
                $rowActivityMap[10] = [];
                $rowActivityMap[11] = [];
                $rowActivityMap[12] = [];
            }
        }

        foreach ($rowActivityMap as $rowIndex => $checkedMonths) {
            if ($rowIndex >= $rows->length) {
                continue;
            }
            $row = $rows->item($rowIndex);
            $cells = $xpath->query('.//w:tc', $row);

            for ($m = 1; $m <= 12; $m++) {
                $cellIndex = $m + 1;
                if ($cellIndex >= $cells->length) {
                    continue;
                }
                $cell = $cells->item($cellIndex);

                $isChecked = in_array($m, (array)$checkedMonths);

                // w:tcPr for cell shading
                $tcPr = $xpath->query('w:tcPr', $cell)->item(0);
                if (!$tcPr) {
                    $tcPr = $dom->createElement('w:tcPr');
                    $cell->insertBefore($tcPr, $cell->firstChild);
                }
                $shd = $xpath->query('w:shd', $tcPr)->item(0);
                if (!$shd) {
                    $shd = $dom->createElement('w:shd');
                    $tcPr->appendChild($shd);
                }

                // Paragraph inside cell
                $p = $xpath->query('w:p', $cell)->item(0);
                if (!$p) {
                    $p = $dom->createElement('w:p');
                    $cell->appendChild($p);
                }

                // Center align paragraph
                $pPr = $xpath->query('w:pPr', $p)->item(0);
                if (!$pPr) {
                    $pPr = $dom->createElement('w:pPr');
                    $p->insertBefore($pPr, $p->firstChild);
                }
                $jc = $xpath->query('w:jc', $pPr)->item(0);
                if (!$jc) {
                    $jc = $dom->createElement('w:jc');
                    $pPr->appendChild($jc);
                }
                $jc->setAttribute('w:val', 'center');

                // Clear previous text runs
                $runs = $xpath->query('w:r', $p);
                foreach ($runs as $r) {
                    $p->removeChild($r);
                }

                if ($isChecked) {
                    $shd->setAttribute('w:val', 'clear');
                    $shd->setAttribute('w:color', 'auto');
                    $shd->setAttribute('w:fill', '93c47d'); // Signature soft green from master KAK template

                    $r = $dom->createElement('w:r');
                    $rPr = $dom->createElement('w:rPr');
                    $b = $dom->createElement('w:b');
                    $rPr->appendChild($b);
                    $sz = $dom->createElement('w:sz');
                    $sz->setAttribute('w:val', '22');
                    $rPr->appendChild($sz);
                    $rFonts = $dom->createElement('w:rFonts');
                    $rFonts->setAttribute('w:ascii', 'Arial');
                    $rFonts->setAttribute('w:hAnsi', 'Arial');
                    $rPr->appendChild($rFonts);
                    $r->appendChild($rPr);
                    $t = $dom->createElement('w:t', '✓');
                    $r->appendChild($t);
                    $p->appendChild($r);
                } else {
                    $shd->setAttribute('w:val', 'clear');
                    $shd->setAttribute('w:color', 'auto');
                    $shd->setAttribute('w:fill', 'FFFFFF');
                }
            }
        }

        $newXml = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $newXml);
        $zip->close();

        return true;
    }
}
