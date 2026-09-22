<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class PrepareKakTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kak:prepare-template {--source= : Custom source docx path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepares the KAK master template by converting highlighted placeholders into merge fields (${field_xxx}) and generating field_map.json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting KAK template preparation...');

        // 1. Determine template path
        $customSource = $this->option('source');
        $possiblePaths = array_filter([
            $customSource,
            base_path('template/Copy of Format Digitalisasi KAK.docx'),
            base_path('template/Copy_of_Format_Digitalisasi_KAK.docx'),
            storage_path('app/templates/Copy of Format Digitalisasi KAK.docx'),
            storage_path('app/templates/kak_template.docx'),
        ]);

        $templatePath = null;
        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                $templatePath = $path;
                break;
            }
        }

        if (!$templatePath) {
            $this->error('Template file not found! Checked paths: ' . implode(', ', $possiblePaths));
            return Command::FAILURE;
        }

        $this->line("Found master template: <comment>{$templatePath}</comment>");

        // 2. Open docx via ZipArchive
        $tempZipPath = storage_path('app/templates/temp_processing.docx');
        @copy($templatePath, $tempZipPath);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) !== true) {
            $this->error("Failed to open docx file: {$tempZipPath}");
            return Command::FAILURE;
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        if (!$xmlContent) {
            $this->error("word/document.xml not found inside docx archive.");
            $zip->close();
            @unlink($tempZipPath);
            return Command::FAILURE;
        }

        // 3. Load XML into DOMDocument
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        // Suppress XML lib errors
        libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // 4. Iterate paragraphs and identify sections + placeholder runs
        $paras = $xpath->query('//w:p');
        $currentSection = '1. Identitas Program & KAK';
        $fieldMap = [];
        $fieldIndex = 0;

        foreach ($paras as $p) {
            $pText = trim(preg_replace('/\s+/u', ' ', $p->textContent));
            $upper = strtoupper($pText);
            $pLen = mb_strlen($pText);

            // Section detection logic: Only consider short paragraphs (headings) to avoid matching inside narrative sentences
            if ($pLen > 0 && $pLen < 70) {
                if (str_contains($upper, 'LATAR BELAKANG') || str_contains($upper, 'DASAR HUKUM')) {
                    $currentSection = '2. Latar Belakang & Dasar Hukum';
                } elseif (str_contains($upper, 'PENERIMA MANFAAT')) {
                    $currentSection = '3. Penerima Manfaat';
                } elseif (str_contains($upper, 'STRATEGI PENCAPAIAN') || str_contains($upper, 'METODE PELAKSANAAN') || str_contains($upper, 'TAHAPAN PELAKSANAAN')) {
                    $currentSection = '4. Strategi Pencapaian Keluaran';
                } elseif (str_contains($upper, 'KURUN WAKTU') || str_contains($upper, 'WAKTU PELAKSANAAN') || str_contains($upper, 'JADWAL PELAKSANAAN')) {
                    $currentSection = '5. Kurun Waktu Pelaksanaan';
                } elseif (str_contains($upper, 'BIAYA') || str_contains($upper, 'ANGGARAN YANG DIBUTUHKAN') || str_contains($upper, 'PERKIRAAN BIAYA')) {
                    $currentSection = '6. Perkiraan Biaya';
                } elseif (str_contains($upper, 'JAKARTA,') || (str_contains($upper, 'ASISTEN DEPUTI') && str_contains($upper, '280987540640')) || str_contains($upper, 'TANDA TANGAN')) {
                    $currentSection = '7. Pengesahan & Tanda Tangan';
                } elseif (str_starts_with($upper, 'LAMPIRAN') || str_contains($upper, 'GENDER ANALYSIS PATHWAY') || str_contains($upper, 'PROFIL RISIKO')) {
                    $currentSection = '8. Lampiran (GAP & Profil Risiko)';
                }
            }

            // Find runs inside this paragraph
            $runs = $xpath->query('.//w:r', $p);
            foreach ($runs as $r) {
                // Check if run has highlight
                $hasHighlight = $xpath->query('w:rPr/w:highlight', $r)->length > 0;
                $tNode = $xpath->query('w:t', $r)->item(0);
                if (!$tNode) {
                    continue;
                }

                $rText = $tNode->nodeValue;
                $trimText = trim($rText);

                if ($trimText === '') {
                    continue;
                }

                // Exclude accidental highlights of single punctuation or connecting words
                if (in_array($trimText, ['.', ',', '(', ')', ':', 'dengan', 'yaitu'])) {
                    continue;
                }

                // Exclude informational notes/footnotes from being converted into user form fields
                if (str_starts_with($trimText, '*akun yang diperkenankan') || 
                    str_contains($trimText, 'bit.ly/PedomanBersamaKPMK') ||
                    str_starts_with($trimText, 'Detai ada pada link')) {
                    continue;
                }

                // Check if this run is a placeholder:
                // Either highlighted, or containing dots/ellipsis/placeholder terms
                $isPlaceholder = false;
                if ($hasHighlight) {
                    $isPlaceholder = true;
                } elseif (preg_match('/[\.…]{3,}/u', $trimText) || str_contains($trimText, 'Rp.xxxxx') || str_contains($trimText, '[......]')) {
                    $isPlaceholder = true;
                }

                if (!$isPlaceholder) {
                    continue;
                }

                // Generate field key: field_001, field_002, ...
                $fieldIndex++;
                $fieldKey = sprintf('field_%03d', $fieldIndex);

                // Build context from surrounding paragraph
                $cleanPara = trim(preg_replace('/\s+/u', ' ', $pText));
                if (mb_strlen($cleanPara) > 160) {
                    // Truncate surrounding context neatly
                    $pos = mb_strpos($cleanPara, $trimText);
                    if ($pos !== false) {
                        $start = max(0, $pos - 40);
                        $len = min(mb_strlen($cleanPara) - $start, 120 + mb_strlen($trimText));
                        $context = ($start > 0 ? '...' : '') . trim(mb_substr($cleanPara, $start, $len)) . '...';
                    } else {
                        $context = mb_substr($cleanPara, 0, 140) . '...';
                    }
                } else {
                    $context = $cleanPara;
                }

                // Replace the placeholder in context with [......] for display
                $displayContext = str_replace($trimText, '[......]', $context);
                if (!str_contains($displayContext, '[......]')) {
                    $displayContext = $cleanPara . ' [......]';
                }

                // Determine if it should be textarea
                $lowerContext = strtolower($context . ' ' . $trimText);
                $isTextarea = str_contains($lowerContext, 'narasi') ||
                    str_contains($lowerContext, 'jelaskan') ||
                    str_contains($lowerContext, 'uraikan') ||
                    str_contains($lowerContext, 'catatan') ||
                    str_contains($lowerContext, 'sebutkan') ||
                    str_contains($lowerContext, 'isu-isu') ||
                    str_contains($lowerContext, 'alasan pemilihan lokasi') ||
                    str_contains($lowerContext, 'output yang akan dicapai') ||
                    str_contains($lowerContext, 'analisis') ||
                    mb_strlen($trimText) > 60;

                // Determine helpful label, code classification, and realistic example placeholder
                $label = $this->generateFieldLabel($trimText, $pText, $currentSection, $fieldIndex);
                $isCode = $this->isCodeField($trimText, $cleanPara, $displayContext, $fieldIndex);
                $placeholder = $this->generateFieldPlaceholder($trimText, $cleanPara, $label, $isCode, $isTextarea, $fieldIndex);

                $fieldMap[] = [
                    'key' => $fieldKey,
                    'section' => $currentSection,
                    'context' => $displayContext,
                    'label' => $label,
                    'original_text' => $trimText,
                    'is_code' => $isCode,
                    'placeholder' => $placeholder,
                    'is_textarea' => $isTextarea,
                    'required' => $this->isFieldRequired($label, $cleanPara, $fieldIndex),
                ];

                // Replace text inside XML with ${field_xxx}
                // Keep all run styles / rPr exactly as they are
                $tNode->nodeValue = '${' . $fieldKey . '}';
                // If w:t had preserved whitespace attribute, keep it
                if ($tNode->hasAttribute('xml:space')) {
                    $tNode->removeAttribute('xml:space');
                }
            }
        }

        // 5. Save updated document.xml back into zip
        $newXml = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $newXml);
        $zip->close();

        // 6. Save destination template copies
        $destDocxResources = resource_path('templates/kak_template_merged.docx');
        $destDocxStorage = storage_path('app/templates/kak_template_merged.docx');
        @copy($tempZipPath, $destDocxResources);
        @copy($tempZipPath, $destDocxStorage);
        @unlink($tempZipPath);

        // 7. Save field_map.json
        $destJsonResources = resource_path('templates/field_map.json');
        $destJsonStorage = storage_path('app/templates/field_map.json');
        $jsonEncoded = json_encode($fieldMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($destJsonResources, $jsonEncoded);
        file_put_contents($destJsonStorage, $jsonEncoded);

        $totalFields = count($fieldMap);
        $this->info("==================================================");
        $this->info("SUCCESS! KAK Template Merge Preparation Complete.");
        $this->info("Total fields converted : {$totalFields}");
        $this->info("Template merged path   : {$destDocxResources}");
        $this->info("Field map JSON path    : {$destJsonResources}");
        $this->info("==================================================");

        // Print section summary
        $sectionCounts = [];
        foreach ($fieldMap as $f) {
            $s = $f['section'];
            $sectionCounts[$s] = ($sectionCounts[$s] ?? 0) + 1;
        }

        $this->table(['Section Name', 'Field Count'], array_map(function ($k, $v) {
            return [$k, $v];
        }, array_keys($sectionCounts), array_values($sectionCounts)));

        return Command::SUCCESS;
    }

    /**
     * Determine if this field is a Code / Identifier field (e.g. (……) in parentheses or RO / KRO / PN code).
     */
    protected function isCodeField(string $trimText, string $cleanPara, string $context, int $index): bool
    {
        // Explicit code fields across all sections of the KAK template:
        if (in_array($index, [4, 6, 8, 15, 17, 19, 21, 24, 41, 43, 46])) {
            return true;
        }

        // Parentheses specifically enclosing dots/ellipses without textual words (e.g. (……) or (.....))
        if (preg_match('/^\([…\.\s\-]+\)$/u', $trimText)) {
            return true;
        }

        return false;
    }

    /**
     * Generate user-friendly field label from surrounding text and original text.
     */
    protected function generateFieldLabel(string $trimText, string $paraText, string $section, int $index): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $paraText));

        // Exact labels by field index
        $indexLabels = [
            1 => 'Nama Asisten Deputi (Cover)',
            2 => 'Tahun Anggaran KAK',
            3 => 'Judul / Topik Rekomendasi Kebijakan',
            4 => 'Kode Rekomendasi Kebijakan (Kode RO / KRO)',
            5 => 'Bidang Kebijakan Koordinasi',
            6 => 'Kode Bidang / Sasaran Strategis (Kode SS)',
            7 => 'Nama Kebijakan Terkait',
            8 => 'Kode Kebijakan / Komponen (Kode Komp)',
            9 => 'Nama Deputi Bidang Pembina',
            10 => 'Tahun Anggaran Pelaksanaan',
            11 => 'Nama Asisten Deputi Pelaksana',
            12 => 'Sasaran Strategis: Kebijakan Bidang yang Dikembangkan',
            13 => 'Indikator Kinerja: Rekomendasi Kebijakan di Bidang yang Dihasilkan',
            14 => 'Nama Kebijakan Target Output',
            15 => 'Kode Indikator Sasaran Kebijakan (Kode Indikator)',
            16 => 'Nama Sasaran Program yang Disusun',
            17 => 'Kode Sasaran Program / Kegiatan (Kode Sasaran)',
            18 => 'Nama Program / Kebijakan Bidang',
            19 => 'Kode Komponen Program (Kode Komp)',
            20 => 'Judul Rekomendasi Alternatif Kebijakan (RAK)',
            21 => 'Kode Rekomendasi Alternatif Kebijakan (Kode RAK)',
            22 => 'Sub-topik Rekomendasi Alternatif Kebijakan',
            23 => 'Target Jumlah Rekomendasi Kebijakan (Angka & Satuan)',
            24 => 'Kode / Nomor Rincian Output (Kode RO)',
            25 => 'Dasar Hukum 1: Undang-Undang',
            26 => 'Dasar Hukum 2: Peraturan Pemerintah',
            27 => 'Dasar Hukum 3: Peraturan Presiden',
            28 => 'Dasar Hukum 4: Permen / Inpres Terkait (dst)',
            29 => 'Catatan Regulasi Lainnya Terkait RO',
            30 => 'Nomor Peraturan Presiden Kemenko PMK',
            31 => 'Tentang Perpres Kemenko PMK',
            32 => 'Bidang Urusan Pemerintahan Kemenko PMK',
            33 => 'Nama Deputi Bidang Pelaksana',
            34 => 'Fungsi Deputi Bidang',
            35 => 'Bidang Kebijakan Deputi',
            36 => 'Nama Asisten Deputi',
            37 => 'Fungsi Asisten Deputi',
            38 => 'Nama Asisten Deputi Pembina K/L',
            39 => 'Daftar K/L Terkait di Bawah Koordinasi',
            40 => 'Nama Asisten Deputi (RPJMN)',
            41 => 'Kode Prioritas Nasional (Kode PN)',
            42 => 'Nama Prioritas Nasional (PN)',
            43 => 'Kode Sasaran Utama PN (Kode SPN)',
            44 => 'Uraian Sasaran Utama PN',
            45 => 'Tahun Rencana Kerja Pemerintah (RKP)',
            46 => 'Kode Sasaran Pembangunan PN',
            47 => 'Arah Kebijakan Sasaran Pembangunan PN',
            48 => 'Bidang Intervensi Kebijakan',
            49 => 'Fokus Intervensi Kebijakan',
            50 => 'Tahun Pengawalan Indikator RPJMN',
            51 => 'Daftar Indikator RPJMN yang Dikawal',
            52 => 'Nama Program / Isu Data Terkini',
            53 => 'Pernyataan Kondisi / Data Terkini',
            54 => 'Label / Judul Isu Strategis',
            55 => 'Uraian Isu Strategis, Kondisi Eksisting & Gap Analysis',
            56 => 'Nama Asisten Deputi (Program Prioritas Lainnya)',
            57 => 'Program Prioritas Lainnya yang Dikoordinasikan',
            58 => 'Rujukan Regulasi Program Prioritas Tambahan',
            59 => 'Nama Asisten Deputi (Tupoksi)',
            60 => 'Output yang Dihasilkan Berdasarkan Tupoksi',
            61 => 'Judul Rekomendasi Kebijakan RAK 1',
            62 => 'Tujuan Rekomendasi RAK 1',
            63 => 'Fokus Sasaran Rekomendasi RAK 1',
            64 => 'Target K/L Pelaksana Rekomendasi RAK 1',
            65 => 'Judul Rekomendasi Kebijakan RAK 2',
            66 => 'Tujuan Rekomendasi RAK 2',
            67 => 'Fokus Sasaran Rekomendasi RAK 2',
            68 => 'Target K/L Pelaksana Rekomendasi RAK 2',
            69 => 'Judul Rekomendasi Kebijakan RAK 3',
            70 => 'Nama Asisten Deputi Pengawal Reformasi Birokrasi (RB)',
            71 => 'Mandat Pengawalan Indikator Reformasi Birokrasi (RB)',
            72 => 'Narasi RB: Catatan Pelaksanaan RB yang Disusun',
            73 => 'Narasi RB: Regulasi / Peraturan Perundangan Dasar',
            74 => 'Narasi RB: Kondisi Capaian Indikator & Target yang Diharapkan',
            75 => 'Narasi RB: Isu Strategis yang Harus Dikawal',
            76 => 'Narasi RB: Faktor Penghambat & Solusi Pendorong',
            77 => 'Narasi RB: Keterhubungan dengan RB Tematik / General',
            78 => 'Narasi RB: Pelibatan Stakeholder K/L dan Pemda',
            79 => 'Integrasi Gender: Nama Asisten Deputi',
            80 => 'Integrasi Gender: Isu Kesenjangan yang Ditemukan',
            81 => 'Integrasi Gender: Faktor Penyebab Kesenjangan',
            82 => 'Integrasi Gender: Bentuk Intervensi Kebijakan Responsif Gender',
            83 => 'Integrasi Gender: Dampak Positif bagi Perempuan dan Anak',
            84 => 'Integrasi Gender: Asisten Deputi Pelaksana Intervensi',
            85 => 'Integrasi Gender: Harapan Capaian Responsif Gender',
            86 => 'Profil Risiko & Rencana Mitigasi (Lampiran 2 KAK)',
            87 => 'Kelompok Sasaran Penerima Manfaat Langsung',
            88 => 'Lembaga / Stakeholder Penerima Manfaat Koordinasi',
            89 => 'Manfaat Langsung yang Diperoleh',
            90 => 'Manfaat Jangka Panjang yang Diharapkan (dst)',
            91 => 'Indikator Keberhasilan Penerima Manfaat',
            92 => 'Data Terpilah Penerima Manfaat (Gender & Disabilitas)',
            93 => 'Metode Pelaksanaan Kegiatan (Rakor, FGD, Konsinyering)',
            94 => 'Uraian Tahap RAK 1',
            95 => 'Uraian Tahap RAK 2',
            96 => 'Uraian Tahap RAK 3',
            97 => 'Cara Pelaksanaan (Swakelola / Kontraktual)',
            98 => 'Tahun Pelaksanaan Seluruh Tahapan Kegiatan',
            99 => 'Pengantar Kalimat Pendahuluan Tahap 1 (Identifikasi)',
            100 => 'Narasi Pendahuluan Tahap 1 (Identifikasi)',
            101 => 'Rincian Kegiatan Pencapaian Output Tahap 1',
            102 => 'Judul Agenda Rapat Koordinasi Tahap 1',
            103 => 'Lokasi Rapat Tahap 1: Provinsi',
            104 => 'Lokasi Rapat Tahap 1: Kabupaten / Kota',
            105 => 'Waktu Rapat Tahap 1: Bulan Pelaksanaan',
            106 => 'Waktu Rapat Tahap 1: Tahun Pelaksanaan',
            107 => 'Peserta / K-L Terkait yang Diundang (Tahap 1)',
            108 => 'Keterangan Narasumber (Tahap 1)',
            109 => 'Output yang Akan Dicapai (Tahap 1)',
            110 => 'Alasan Pemilihan Lokasi (Bila di Luar DKI) (Tahap 1)',
            111 => 'Pengantar Kalimat Pendahuluan Tahap 2 (Sinkronisasi & Koordinasi)',
            112 => 'Narasi Pendahuluan Tahap 2 (Sinkronisasi & Koordinasi)',
            113 => 'Rincian Kegiatan Pencapaian Output Tahap 2',
            114 => 'Lokasi Rapat Tahap 2: Provinsi',
            115 => 'Lokasi Rapat Tahap 2: Kabupaten / Kota',
            116 => 'Waktu Rapat Tahap 2: Bulan Pelaksanaan',
            117 => 'Waktu Rapat Tahap 2: Tahun Pelaksanaan',
            118 => 'Peserta / K-L Terkait yang Diundang (Tahap 2)',
            119 => 'Keterangan Narasumber (Tahap 2)',
            120 => 'Output yang Akan Dicapai (Tahap 2)',
            121 => 'Alasan Pemilihan Lokasi (Bila di Luar DKI) (Tahap 2)',
            122 => 'Catatan Tindak Lanjut Tahap 2',
            123 => 'Keterangan Tambahan Lainnya (Tahap 2)',
            124 => 'Pengantar Kalimat Pendahuluan Tahap 3 (Pengendalian & Monev)',
            125 => 'Narasi Pendahuluan Tahap 3 (Pengendalian & Monev)',
            126 => 'Rincian Kegiatan Monev dan Pengendalian Risiko Tahap 3',
            127 => 'Lokasi Monev Tahap 3: Provinsi',
            128 => 'Lokasi Monev Tahap 3: Kabupaten / Kota',
            129 => 'Waktu Monev Tahap 3: Bulan Pelaksanaan',
            130 => 'Waktu Monev Tahap 3: Tahun Pelaksanaan',
            131 => 'Peserta / K-L Terkait yang Diundang (Tahap 3)',
            132 => 'Keterangan Narasumber (Tahap 3)',
            133 => 'Output yang Akan Dicapai (Tahap 3)',
            134 => 'Alasan Pemilihan Lokasi (Bila di Luar DKI) (Tahap 3)',
            135 => 'Catatan Evaluasi Lapangan (Tahap 3)',
            136 => 'Tindak Lanjut Temuan Lapangan (Tahap 3)',
            137 => 'Pengantar Kalimat Pendahuluan Tahap 4 (Perumusan Rekomendasi)',
            138 => 'Narasi Pendahuluan Tahap 4 (Perumusan Rekomendasi)',
            139 => 'Rincian Kegiatan Perumusan Naskah Rekomendasi Tahap 4',
            140 => 'Lokasi Konsinyering Tahap 4: Provinsi',
            141 => 'Lokasi Konsinyering Tahap 4: Kabupaten / Kota',
            142 => 'Waktu Konsinyering Tahap 4: Bulan Pelaksanaan',
            143 => 'Waktu Konsinyering Tahap 4: Tahun Pelaksanaan',
            144 => 'Peserta / K-L Terkait yang Diundang (Tahap 4)',
            145 => 'Keterangan Narasumber (Tahap 4)',
            146 => 'Output yang Akan Dicapai (Tahap 4)',
            147 => 'Alasan Pemilihan Lokasi (Bila di Luar DKI) (Tahap 4)',
            148 => 'Tindak Lanjut Pengesahan Naskah Rekomendasi Tahap 4',
            149 => 'Catatan Publikasi dan Diseminasi Rekomendasi Tahap 4',
            150 => 'Total Anggaran yang Dibutuhkan (Nominal Angka)',
            151 => 'Rincian Akun Belanja yang Diperkenankan',
            152 => 'Nama Asisten Deputi (Bagian Penutup Biaya)',
            153 => 'Tahun Anggaran Pelaksanaan (Bagian Penutup Biaya)',
            154 => 'Jumlah Target Output (Bagian Penutup Biaya)',
            155 => 'Total Nominal Anggaran Biaya (Rupiah di Penutup)',
            156 => 'Total Anggaran Biaya dalam Narasi Terbilang (Rupiah di Penutup)',
            157 => 'Tempat dan Tanggal Pengesahan KAK',
            158 => 'Jabatan Pejabat Penandatangan (Asisten Deputi ...)',
            159 => 'Nama Lengkap Pejabat Penandatangan',
            160 => 'NIP Pejabat Penandatangan',
        ];

        if (isset($indexLabels[$index])) {
            return $indexLabels[$index];
        }

        // Fallback for any unexpected index
        if ($this->isCodeField($trimText, $clean, $clean, $index)) {
            return "Kode / Nomor Identifikasi (Kode)";
        }

        if (mb_strlen($trimText) > 4 && !str_starts_with($trimText, '……')) {
            return mb_substr($trimText, 0, 60);
        }

        return mb_substr($clean, 0, 70);
    }

    /**
     * Generate clear, realistic placeholder examples so users know what to type.
     */
    protected function generateFieldPlaceholder(string $trimText, string $cleanPara, string $label, bool $isCode, bool $isTextarea, int $index): string
    {
        // Exact placeholders by field index
        $indexPlaceholders = [
            1 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            2 => 'Contoh: 2026',
            3 => 'Contoh: Rekomendasi Kebijakan Peningkatan Akses Layanan Ramah Anak di Wilayah Rawan Bencana',
            4 => 'Contoh kode: 4321.BMA.001 / RO.01',
            5 => 'Contoh: Peningkatan Kualitas Anak, Perempuan, dan Pemuda',
            6 => 'Contoh kode: SS.02 / KRO.01',
            7 => 'Contoh: Kebijakan Perlindungan Anak Terpadu Berbasis Komunitas',
            8 => 'Contoh kode: 051 / KOMP.01',
            9 => 'Contoh: Peningkatan Kualitas Anak, Perempuan, dan Pemuda',
            10 => 'Contoh: 2026',
            11 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            12 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            13 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            14 => 'Contoh: Penguatan Layanan Pengasuhan dan Perlindungan Anak Korban Bencana',
            15 => 'Contoh kode: IKK.01 / 001.0A',
            16 => 'Contoh: Peningkatan Kapasitas Layanan Responsif Gender dan Anak di Daerah Rawan',
            17 => 'Contoh kode: SP.02 / 4321',
            18 => 'Contoh: Koordinasi Pelaksanaan Kebijakan Hak dan Perlindungan Anak',
            19 => 'Contoh kode: 051.A / KOMP.02',
            20 => 'Contoh: Rekomendasi Percepatan Integrasi Layanan Perlindungan Anak di Wilayah Terdampak Bencana',
            21 => 'Contoh kode: RAK-01 / 4321.BMA',
            22 => 'Contoh: Penyediaan Fasilitas Ramah Anak dan Mekanisme Rujukan Terpadu di Tempat Pengungsian',
            23 => 'Contoh: 1 (satu) Rekomendasi Kebijakan',
            24 => 'Contoh kode: 4321.BMA.001 / RO.01',
            25 => 'Contoh: UU No. 35 Tahun 2014 tentang Perubahan atas UU No. 23 Tahun 2002 tentang Perlindungan Anak',
            26 => 'Contoh: PP No. 78 Tahun 2021 tentang Perlindungan Khusus Bagi Anak',
            27 => 'Contoh: Perpres No. 35 Tahun 2020 tentang Kementerian Koordinator Bidang Pembangunan Manusia dan Kebudayaan',
            28 => 'Contoh: Permenko PMK No. 4 Tahun 2020 tentang Organisasi dan Tata Kerja Kemenko PMK',
            29 => 'Contoh: Cantumkan regulasi kementerian teknis yang menjadi acuan langsung penyusunan rekomendasi kebijakan',
            30 => 'Contoh: 35 Tahun 2020',
            31 => 'Contoh: Kementerian Koordinator Bidang Pembangunan Manusia dan Kebudayaan',
            32 => 'Contoh: Pembangunan Manusia dan Kebudayaan',
            33 => 'Contoh: Peningkatan Kualitas Anak, Perempuan, dan Pemuda',
            34 => 'Contoh: Koordinasi dan sinkronisasi perumusan, penetapan, dan pelaksanaan kebijakan',
            35 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            36 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            37 => 'Contoh: Koordinasi, perumusan rekomendasi kebijakan, serta pemantauan dan evaluasi',
            38 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            39 => 'Contoh: Kementerian PPPA, Kemensos, Kemendikbudristek, dan Kemenkes',
            40 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            41 => 'Contoh kode: PN 1 (atau PN 3)',
            42 => 'Contoh: Peningkatan Sumber Daya Manusia Berkualitas dan Berdaya Saing',
            43 => 'Contoh kode: SPN-01 / Sasaran 2',
            44 => 'Contoh: Meningkatnya Kesejahteraan dan Perlindungan Perempuan dan Anak',
            45 => 'Contoh: 2026',
            46 => 'Contoh kode: PN 3.B / RKP-02',
            47 => 'Contoh: Penguatan pencegahan tindak kekerasan dan pengasuhan anak berkualitas',
            48 => 'Contoh: Perlindungan Khusus Anak dan Pencegahan Kekerasan',
            49 => 'Contoh: Penguatan koordinasi terpadu pemenuhan hak anak di lokasi tanggap darurat bencana',
            50 => 'Contoh: 2026',
            51 => 'Contoh: 1) Prevalensi kekerasan anak turun menjadi 10%; 2) Persentase layanan korban anak 90%',
            52 => 'Contoh: Perlindungan Khusus Anak di Daerah Rawan Bencana',
            53 => 'Contoh: Data Bappenas 2025 menunjukkan 65% fasilitas pengungsian belum memiliki pos ramah anak terpadu',
            54 => 'Contoh: Isu-isu strategis dan gap analysis usulan SKP',
            55 => 'Contoh: Uraikan permasalahan objek SKP saat ini, capaian vs target, kesenjangan data indikator terpilah gender, dan fokus kewilayahan prioritas...',
            56 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            57 => 'Contoh: Strategi Nasional Pencegahan dan Penanganan Perkawinan Anak (Stranas PPA)',
            58 => 'Contoh: Perpres No. 101 Tahun 2022 tentang Strategi Nasional Penghapusan Kekerasan terhadap Anak',
            59 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            60 => 'Contoh: 1 (satu) Dokumen Rekomendasi Alternatif Kebijakan Penanganan Ramah Anak Terpadu',
            61 => 'Contoh: Rekomendasi Kebijakan Standarisasi Layanan Ramah Anak di Wilayah Pengungsian Bencana',
            62 => 'Contoh: Menjamin perlindungan fisik, psikososial, dan pemenuhan hak dasar anak korban bencana secara cepat',
            63 => 'Contoh: Penyusunan protokol bersama pemenuhan hak anak dan penyediaan ruang ramah anak di tenda evakuasi',
            64 => 'Contoh: BNPB, Kemensos, Kementerian PPPA, dan BPBD Daerah',
            65 => 'Contoh: Rekomendasi Peningkatan Kapasitas SDM Pendamping dan Fasilitator Psikososial Anak di Daerah Rawan Bencana',
            66 => 'Contoh: Meningkatkan kesiapsiagaan tenaga pendamping perlindungan anak lintas sektor daerah',
            67 => 'Contoh: Pelatihan terpadu penanganan trauma anak bencana bagi relawan dan pekerja sosial',
            68 => 'Contoh: Kemensos, Kemenkes, Kemendikbudristek, dan Pemda setempat',
            69 => 'Contoh: Rekomendasi Penguatan Sistem Data Terpilah Anak Terdampak Bencana Berbasis Digital',
            70 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            71 => 'Contoh: 1) Indeks Pelayanan Publik (IPP) predikat A; 2) Nilai Evaluasi SAKIP minimal BB; 3) Indeks Kualitas Kebijakan (IKK) predikat Sangat Baik',
            72 => 'Contoh: Penyusunan rekomendasi kebijakan ini mengadopsi prinsip digitalisasi birokrasi, transparansi tata kelola data, dan akuntabilitas lintas instansi',
            73 => 'Contoh: PermenPANRB No. 3 Tahun 2023 tentang Perubahan Roadmap Reformasi Birokrasi 2020-2024',
            74 => 'Contoh: Capaian IKK tahun sebelumnya 78,5 dan ditargetkan meningkat menjadi 85,0 pada tahun pelaksanaan',
            75 => 'Contoh: Pencegahan duplikasi program antar-kementerian dan efisiensi belanja koordinasi lintas instansi',
            76 => 'Contoh: Penghambat: Silo data sektoral antar lembaga. Pendorong: Integrasi dashboard satu data bencana berbasis NIK',
            77 => 'Contoh: Terhubung langsung dengan RB Tematik Pengentasan Kemiskinan dan Digitalisasi Administrasi Pemerintahan',
            78 => 'Contoh: Melibatkan KPPPA, Kemensos, Bappenas, Kemenkeu, BPS, serta 5 Pemda percontohan',
            79 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            80 => 'Contoh: Anak perempuan di pos pengungsian memiliki risiko 3x lipat mengalami pelecehan seksual dan minim akses sanitasi layak',
            81 => 'Contoh: Ketiadaan bilik mandi terpisah dan minimnya penerangan di kamp penampungan darurat',
            82 => 'Contoh: Menetapkan mandatori sekat privasi keluarga dan pos siaga pengaduan kekerasan berbasis gender 24 jam',
            83 => 'Contoh: Meningkatnya rasa aman anak perempuan dan terpenuhinya kebutuhan khusus ibu menyusui dan balita di pos evakuasi',
            84 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            85 => 'Contoh: Terciptanya ekosistem tanggap darurat yang inklusif bagi seluruh kelompok rentan tanpa diskriminasi',
            86 => 'Contoh: Risiko: Terhambatnya sinkronisasi data antar-K/L. Analisis: Perbedaan format data. Mitigasi: Penyeragaman format bridging API data darurat anak.',
            87 => 'Contoh: Anak-anak korban bencana, perempuan rentan, serta keluarga terdampak di daerah rawan bencana',
            88 => 'Contoh: K/L teknis pembina anak, Dinas Sosial, Dinas PPPA, BPBD, dan Pekerja Sosial Daerah',
            89 => 'Contoh: Tersedianya kepastian layanan perlindungan, logistik spesifik anak, dan fasilitas pengungsian ramah anak',
            90 => 'Contoh: Terwujudnya ketangguhan keluarga dan sistem perlindungan anak nasional yang adaptif terhadap situasi krisis bencana',
            91 => 'Contoh: 100% anak di pos pengungsian prioritas terlayani kebutuhan dasar dan perlindungan khususnya',
            92 => 'Contoh: Data terpilah mencakup: 1.250 anak laki-laki, 1.420 anak perempuan, 85 anak penyandang disabilitas, dan 120 balita terlantar',
            93 => 'Contoh: Rapat Koordinasi Lintas Sektor, Focus Group Discussion (FGD) Pakar, dan Kunjungan Lapangan / Fact-Finding',
            94 => 'Contoh: RAK 1: Rapat Koordinasi Identifikasi Isu Permasalahan dan Kebutuhan Kebijakan Perlindungan Anak di Wilayah Bencana',
            95 => 'Contoh: RAK 2: Sinkronisasi Regulasi dan Penguatan Program Lintas Sektor K/L dalam Penanganan Darurat Perlindungan Anak',
            96 => 'Contoh: RAK 3: Penyusunan Rekomendasi Kebijakan dan Rencana Aksi Terpadu Penanganan Anak Bencana Multisektor',
            97 => 'Contoh: Swakelola Tipe I / Kontraktual',
            98 => 'Contoh: 2026',
            99 => 'Contoh: Berikut merupakan tahapan identifikasi masalah dan perumusan isu strategis kebijakan:',
            100 => 'Contoh: Sebagai koordinator, Kemenko PMK menghimpun informasi lintas sektor terkait isu darurat anak di daerah bencana melalui integrasi masukan K/L teknis...',
            101 => 'Contoh: Pelaksanaan Rapat Koordinasi Identifikasi Masalah dan Penilaian Kebutuhan Lapangan Penanganan Ramah Anak',
            102 => 'Contoh: Rapat Koordinasi Identifikasi Isu Kesenjangan Layanan Anak di Wilayah Rawan Bencana',
            103 => 'Contoh: D.I. Yogyakarta',
            104 => 'Contoh: Kab. Sleman',
            105 => 'Contoh: Maret',
            106 => 'Contoh: 2026',
            107 => 'Contoh: Kemenko PMK, Kementerian PPPA, Kemensos, BNPB, Pemprov DIY, Pemkab Sleman, dan UNICEF',
            108 => 'Contoh: Ada (Tenaga Ahli Perlindungan Anak UNICEF dan Pakar Penanggulangan Bencana UGM)',
            109 => 'Contoh: Matriks identifikasi masalah kebijakan dan daftar isu kritis penanganan anak korban bencana',
            110 => 'Contoh: Kab. Sleman merupakan kawasan rawan bencana erupsi Merapi dengan kesiapan sistem koordinasi anak terbaik sebagai role model',
            111 => 'Contoh: Berikut merupakan tahapan sinkronisasi dan koordinasi kebijakan lintas sektor:',
            112 => 'Contoh: Tahap ini memastikan program kementerian/lembaga dan pemda telah sinkron dan mengakomodasi kebutuhan anak serta kelompok rentan...',
            113 => 'Contoh: Rapat Koordinasi Teknis Sinkronisasi Program dan Anggaran Penanganan Darurat Perlindungan Anak',
            114 => 'Contoh: Jawa Barat',
            115 => 'Contoh: Kab. Cianjur',
            116 => 'Contoh: Juni',
            117 => 'Contoh: 2026',
            118 => 'Contoh: Kemenko PMK, Bappenas, Kemenkeu, Kemensos, Kemenkes, BPBD Jabar, dan Forum Relawan Peduli Anak',
            119 => 'Contoh: Ada (Direktur Perlindungan Sosial Korban Bencana Kemensos & Direktur Agama, Pendidikan dan Kebudayaan Bappenas)',
            120 => 'Contoh: Draft kesepakatan pembagian peran K/L dan integrasi alokasi belanja tanggap darurat anak',
            121 => 'Contoh: Kab. Cianjur merupakan lokasi pemulihan pascabencana gempa bumi yang membutuhkan keberlanjutan intervensi layanan anak terpadu',
            122 => 'Contoh: Penyusunan nota kesepakatan komitmen bersama lintas K/L untuk pengawalan program',
            123 => 'Contoh: Melibatkan perwakilan Forum Anak Daerah sebagai narasumber pengalaman langsung',
            124 => 'Contoh: Berikut merupakan tahapan pengendalian, monitoring dan evaluasi lapangan:',
            125 => 'Contoh: Sebagai koordinator, Kemenko PMK melakukan monitoring dan evaluasi efektivitas intervensi serta kendala pemenuhan hak anak di lapangan...',
            126 => 'Contoh: Kunjungan Monitoring Lapangan dan Rapat Evaluasi Progres Penyelesaian Masalah',
            127 => 'Contoh: Sumatera Barat',
            128 => 'Contoh: Kab. Agam',
            129 => 'Contoh: September',
            130 => 'Contoh: 2026',
            131 => 'Contoh: Tim Terpadu Kemenko PMK, Inspektorat Wilayah, Pemprov Sumbar, BPBD, dan Perwakilan Masyarakat',
            132 => 'Contoh: Ada (Tim Pakar Evaluasi Kebijakan Publik Universitas Andalas)',
            133 => 'Contoh: Laporan hasil verifikasi lapangan dan rekomendasi solusi atas hambatan penanganan di daerah',
            134 => 'Contoh: Kab. Agam terdampak bencana banjir lahar dingin yang memerlukan asesmen langsung pemulihan fasilitas pendidikan dan psikososial anak',
            135 => 'Contoh: Hasil review disajikan dalam format dashboard monev interaktif',
            136 => 'Contoh: Pengiriman tim pendamping teknis percepatan layanan rujukan psikososial',
            137 => 'Contoh: Berikut merupakan tahapan finalisasi penyusunan rekomendasi kebijakan:',
            138 => 'Contoh: Tahap akhir berupa perumusan naskah rekomendasi kebijakan berbasis bukti lapangan, analisis GAP gender, dan kesepakatan lintas sektor...',
            139 => 'Contoh: Konsinyering Penyusunan dan Finalisasi Naskah Rekomendasi Alternatif Kebijakan Terpadu',
            140 => 'Contoh: DKI Jakarta',
            141 => 'Contoh: Kota Jakarta Pusat',
            142 => 'Contoh: November',
            143 => 'Contoh: 2026',
            144 => 'Contoh: Para Pejabat Pimpinan Tinggi Pratama K/L Terkait, Tenaga Ahli, dan Tim Kerja Penyusun KAK',
            145 => 'Contoh: Ada (Staf Khusus Menko PMK dan Guru Besar Hukum Perlindungan Anak UI)',
            146 => 'Contoh: Naskah Final Rekomendasi Alternatif Kebijakan yang siap diserahkan kepada Menteri Koordinator PMK',
            147 => 'Contoh: Dilaksanakan di DKI Jakarta dekat dengan kantor pusat kementerian lembaga pemangku kebijakan utama',
            148 => 'Contoh: Penyerahan naskah policy brief kepada Menko PMK dan pimpinan K/L teknis',
            149 => 'Contoh: Penerbitan policy brief digital di portal Satu Data Kemenko PMK',
            150 => 'Contoh: Rp. 850.000.000,-',
            151 => 'Contoh: Belanja Bahan (521211), Belanja Honor Narasumber (522151), Biaya Transport Lokal (524113), Biaya Paket Meeting Luar Kota (524114)',
            152 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            153 => 'Contoh: 2026',
            154 => 'Contoh: 1 (satu)',
            155 => 'Contoh: Rp. 850.000.000,-',
            156 => 'Contoh: Delapan Ratus Lima Puluh Juta Rupiah',
            157 => 'Contoh: Jakarta, 15 Januari 2026',
            158 => 'Contoh: Pemenuhan Hak dan Perlindungan Anak',
            159 => 'Contoh: Drs. Budi Santoso, M.Si',
            160 => 'Contoh: 19750512 199903 1 003',
        ];

        if (isset($indexPlaceholders[$index])) {
            return $indexPlaceholders[$index];
        }

        if ($isCode) {
            return 'Contoh kode: 001.0A / KRO.01 / RO.02';
        }

        if ($isTextarea) {
            return 'Tuliskan uraian narasi secara lengkap dan jelas di sini...';
        }

        return 'Isi keterangan di sini...';
    }

    /**
     * Determine if a field is primary/required.
     */
    protected function isFieldRequired(string $label, string $para, int $index): bool
    {
        // Primary fields: Asisten Deputi, Tahun Anggaran, Judul / Topik
        return $index <= 3;
    }
}

