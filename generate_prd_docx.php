<?php
/**
 * Script Pembuat Dokumen PRD Sistem Digitalisasi KAK (.docx)
 * Disusun secara formal menggunakan PhpOffice\PhpWord
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

$phpWord = new PhpWord();

// -------------------------------------------------------------
// KONFIGURASI DEFAULT DOKUMEN & FONT
// -------------------------------------------------------------
$phpWord->setDefaultFontName('Calibri');
$phpWord->setDefaultFontSize(10.5);

// Definisi Warna Tema (Modern Government Palette)
$colorNavyDark    = '1B365D'; // Warna Utama / Navy Kemenko PMK
$colorBlueAccent  = '2B6CB0'; // Warna Sekunder / Biru Terang
$colorSlateDark   = '2D3748'; // Warna Teks Utama
$colorSlateMuted  = '4A5568'; // Warna Teks Sekunder
$colorBorderLight = 'CBD5E0'; // Border Tabel
$colorBgHeader    = '1B365D'; // Background Header Tabel
$colorBgAltRow    = 'F7FAFC'; // Background Baris Genap
$colorBgCallout   = 'EBF4FF'; // Background Kotak Catatan
$colorBorderNote  = '3182CE'; // Border Kotak Catatan

// Style Heading
$phpWord->addTitleStyle(1, [
    'name' => 'Calibri', 'size' => 15, 'bold' => true, 'color' => $colorNavyDark
], [
    'spaceBefore' => 240, 'spaceAfter' => 100, 'keepNext' => true
]);

$phpWord->addTitleStyle(2, [
    'name' => 'Calibri', 'size' => 12.5, 'bold' => true, 'color' => $colorBlueAccent
], [
    'spaceBefore' => 180, 'spaceAfter' => 80, 'keepNext' => true
]);

$phpWord->addTitleStyle(3, [
    'name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => $colorSlateDark
], [
    'spaceBefore' => 120, 'spaceAfter' => 60, 'keepNext' => true
]);

// Helper Styling
$paraJustify = ['alignment' => Jc::BOTH, 'spaceAfter' => 100, 'lineHeight' => 1.15];
$paraLeft    = ['alignment' => Jc::START, 'spaceAfter' => 100, 'lineHeight' => 1.15];
$paraCenter  = ['alignment' => Jc::CENTER, 'spaceAfter' => 100];
$fontBody    = ['name' => 'Calibri', 'size' => 10.5, 'color' => $colorSlateDark];
$fontBodyBold= ['name' => 'Calibri', 'size' => 10.5, 'bold' => true, 'color' => $colorSlateDark];
$fontItalic  = ['name' => 'Calibri', 'size' => 10, 'italic' => true, 'color' => $colorSlateMuted];
$fontSmall   = ['name' => 'Calibri', 'size' => 9, 'color' => $colorSlateMuted];

// Helper Function: Tambah Paragraf dengan style standar
function addP($section, $text, $bold = false) {
    global $paraJustify, $fontBody, $fontBodyBold;
    $style = $bold ? $fontBodyBold : $fontBody;
    $section->addText($text, $style, $paraJustify);
}

// Helper Function: Tambah Callout Box
function addCallout($section, $title, $text) {
    global $colorBgCallout, $colorBorderNote, $colorNavyDark, $colorSlateDark;
    $table = $section->addTable([
        'borderColor' => $colorBorderNote,
        'borderLeftSize' => 24, // tebal di sisi kiri
        'borderTopSize' => 0,
        'borderRightSize' => 0,
        'borderBottomSize' => 0,
        'bgColor' => $colorBgCallout,
        'cellMarginTop' => 120,
        'cellMarginBottom' => 120,
        'cellMarginLeft' => 160,
        'cellMarginRight' => 160,
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);
    $table->addRow();
    $cell = $table->addCell(9500);
    $cell->addText($title, ['name' => 'Calibri', 'size' => 10.5, 'bold' => true, 'color' => $colorNavyDark], ['spaceAfter' => 40]);
    $cell->addText($text, ['name' => 'Calibri', 'size' => 10, 'color' => $colorSlateDark], ['spaceAfter' => 0, 'alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
}

// -------------------------------------------------------------
// SEKSI 1: COVER PAGE
// -------------------------------------------------------------
$coverSection = $phpWord->addSection([
    'marginTop' => 1440,
    'marginBottom' => 1440,
    'marginLeft' => 1440,
    'marginRight' => 1440,
]);

$coverSection->addTextBreak(2);

// Badge / Kategori Dokumen
$badgeTable = $coverSection->addTable(['alignment' => JcTable::CENTER]);
$badgeTable->addRow();
$badgeCell = $badgeTable->addCell(5000, [
    'bgColor' => 'E2E8F0',
    'cellMarginTop' => 60,
    'cellMarginBottom' => 60,
    'cellMarginLeft' => 120,
    'cellMarginRight' => 120
]);
$badgeCell->addText('DOKUMEN SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK', [
    'name' => 'Calibri', 'size' => 9, 'bold' => true, 'color' => $colorNavyDark, 'letterSpacing' => 40
], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

$coverSection->addTextBreak(1);

// Judul PRD
$coverSection->addText('PRODUCT REQUIREMENTS DOCUMENT (PRD)', [
    'name' => 'Calibri', 'size' => 24, 'bold' => true, 'color' => $colorNavyDark
], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);

// Subjudul
$coverSection->addText('Sistem Digitalisasi Dokumen Kerangka Acuan Kegiatan (KAK)', [
    'name' => 'Calibri', 'size' => 15, 'bold' => true, 'color' => $colorBlueAccent
], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);

$coverSection->addText('Portal Penyusunan, Otomasi Templating Word (.docx), dan Konversi PDF Instan', [
    'name' => 'Calibri', 'size' => 11, 'italic' => true, 'color' => $colorSlateMuted
], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

// Garis Pembatas
$ruleTable = $coverSection->addTable(['alignment' => JcTable::CENTER]);
$ruleTable->addRow();
$ruleCell = $ruleTable->addCell(8500, [
    'borderBottomSize' => 18,
    'borderBottomColor' => $colorNavyDark
]);
$ruleCell->addText('', [], ['spaceAfter' => 0]);

$coverSection->addTextBreak(4);

// Metadata Dokumen di Cover
$metaTable = $coverSection->addTable([
    'alignment' => JcTable::CENTER,
    'cellMarginTop' => 60,
    'cellMarginBottom' => 60,
    'cellMarginLeft' => 100,
    'cellMarginRight' => 100,
]);

$metaRows = [
    ['Instansi / Organisasi', ': Kementerian Koordinator Bidang Pembangunan Manusia dan Kebudayaan (Kemenko PMK)'],
    ['Unit Kerja / Pemilik Sistem', ': Biro Perencanaan dan Kerjasama / Asisten Deputi'],
    ['Kode Proyek', ': PRJ-KAK-2026-01'],
    ['Penyusun (Author)', ': Tim Pengembang Sistem Informasi & Tata Kelola SPBE'],
    ['Peran Penulis', ': Business Analyst & Software Engineer'],
    ['Versi Dokumen', ': 1.0.0 (Final Draft)'],
    ['Tanggal Rilis', ': 23 September 2026'],
    ['Status Dokumen', ': Disetujui untuk Tahap Implementasi (Approved)'],
    ['Klasifikasi Akses', ': Terbatas / Internal Instansi Pemerintah']
];

foreach ($metaRows as $row) {
    $metaTable->addRow();
    $metaTable->addCell(3000)->addText($row[0], ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'color' => $colorSlateDark], ['spaceAfter' => 30]);
    $metaTable->addCell(5500)->addText($row[1], ['name' => 'Calibri', 'size' => 10, 'color' => $colorSlateDark], ['spaceAfter' => 30]);
}

$coverSection->addTextBreak(3);

// Catatan Kerahasiaan
$coverSection->addText('Dokumen ini bersifat rahasia dan merupakan hak milik internal instansi. Informasi yang terdapat di dalam dokumen ini tidak diperkenankan untuk digandakan, disebarluaskan, atau dialihmediakan tanpa izin tertulis.', [
    'name' => 'Calibri', 'size' => 8.5, 'italic' => true, 'color' => '718096'
], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

// -------------------------------------------------------------
// SEKSI 2: KONTEN UTAMA (DENGAN HEADER & FOOTER)
// -------------------------------------------------------------
$section = $phpWord->addSection([
    'marginTop' => 1440,
    'marginBottom' => 1440,
    'marginLeft' => 1440,
    'marginRight' => 1440,
]);

// Header Dokumen
$header = $section->addHeader();
$headerTable = $header->addTable(['width' => 100 * 50, 'unit' => 'pct']);
$headerTable->addRow();
$headerCellLeft = $headerTable->addCell(6500);
$headerCellLeft->addText('Sistem Digitalisasi KAK — Product Requirements Document (PRD)', [
    'name' => 'Calibri', 'size' => 8.5, 'italic' => true, 'color' => '718096'
], ['spaceAfter' => 0]);
$headerCellRight = $headerTable->addCell(3000);
$headerCellRight->addText('Versi 1.0.0 | Kemenko PMK', [
    'name' => 'Calibri', 'size' => 8.5, 'bold' => true, 'color' => $colorNavyDark
], ['alignment' => Jc::END, 'spaceAfter' => 0]);

// Footer Dokumen
$footer = $section->addFooter();
$footerTable = $footer->addTable(['width' => 100 * 50, 'unit' => 'pct']);
$footerTable->addRow();
$footerLeft = $footerTable->addCell(5000);
$footerLeft->addText('Klasifikasi: Terbatas / Internal Pemerintah', [
    'name' => 'Calibri', 'size' => 8.5, 'color' => '718096'
], ['spaceAfter' => 0]);
$footerRight = $footerTable->addCell(4500);
$footerRight->addPreserveText('Halaman {PAGE} dari {NUMPAGES}', [
    'name' => 'Calibri', 'size' => 8.5, 'bold' => true, 'color' => $colorNavyDark
], ['alignment' => Jc::END, 'spaceAfter' => 0]);

// Helper Styling Tabel
$tblStyleBasic = [
    'borderColor' => $colorBorderLight,
    'borderSize' => 6,
    'cellMarginTop' => 80,
    'cellMarginBottom' => 80,
    'cellMarginLeft' => 120,
    'cellMarginRight' => 120,
    'alignment' => JcTable::CENTER,
    'width' => 100 * 50,
    'unit' => 'pct'
];
$firstRowStyle = ['bgColor' => $colorBgHeader];
$fontTblHeader = ['name' => 'Calibri', 'size' => 9.5, 'bold' => true, 'color' => 'FFFFFF'];
$fontTblCell   = ['name' => 'Calibri', 'size' => 9, 'color' => $colorSlateDark];
$fontTblCellB  = ['name' => 'Calibri', 'size' => 9, 'bold' => true, 'color' => $colorSlateDark];
$fontTblCellCode= ['name' => 'Consolas', 'size' => 8.5, 'color' => $colorNavyDark];

// -------------------------------------------------------------
// DAFTAR ISI (TABLE OF CONTENTS)
// -------------------------------------------------------------
$section->addTitle('Daftar Isi', 1);
addP($section, 'Dokumen ini menggunakan penomoran heading bertingkat resmi. Setelah membuka dokumen di aplikasi pengolah kata (Microsoft Word / LibreOffice Writer), silakan klik kanan area di bawah ini dan pilih "Update Field" untuk memperbarui nomor halaman otomatis.');
$section->addTOC(['name' => 'Calibri', 'size' => 10], ['tabLeader' => \PhpOffice\PhpWord\Style\TOC::TAB_LEADER_DOT], 1, 3);
$section->addPageBreak();

// -------------------------------------------------------------
// 1. INFORMASI DOKUMEN
// -------------------------------------------------------------
$section->addTitle('1. Informasi Dokumen', 1);

addP($section, 'Dokumen Product Requirements Document (PRD) ini menjabarkan spesifikasi fungsional, non-fungsional, arsitektur teknis, dan batasan implementasi dari Sistem Digitalisasi Kerangka Acuan Kegiatan (KAK). Dokumen ini berfungsi sebagai acuan resmi bagi pemangku kepentingan (stakeholder), analis sistem, dan tim teknis pengembang dalam merealisasikan aplikasi.');

$section->addTitle('1.1. Identitas Dokumen', 2);
$docInfoTbl = $section->addTable($tblStyleBasic);
$docInfoData = [
    ['Nama Produk / Sistem', 'Sistem Digitalisasi Kerangka Acuan Kegiatan (KAK)'],
    ['Singkatan / Alias', 'SIM-KAK / E-KAK Digital'],
    ['Nomor Dokumen', 'PRD-SPBE-KAK-2026-V1'],
    ['Penyusun (Author)', 'Tim Rekayasa Perangkat Lunak & Analis Bisnis SPBE'],
    ['Status Persetujuan', 'Final Draft (Siap Masuk Tahap SIKLUS SPRINT-1)'],
    ['Target Implementasi', 'Lingkup Deputi & Asisten Deputi Kemenko PMK'],
    ['Tanggal Efektif', '23 September 2026']
];
foreach ($docInfoData as $r) {
    $docInfoTbl->addRow();
    $docInfoTbl->addCell(3500, ['bgColor' => 'F1F5F9'])->addText($r[0], $fontTblCellB, ['spaceAfter' => 0]);
    $docInfoTbl->addCell(6000)->addText($r[1], $fontTblCell, ['spaceAfter' => 0]);
}
$section->addTextBreak(1);

$section->addTitle('1.2. Riwayat Revisi Dokumen (Version History)', 2);
addP($section, 'Tabel berikut mendokumentasikan siklus revisi dan perubahan substantif pada dokumen spesifikasi ini:');

$revTable = $section->addTable($tblStyleBasic);
$revTable->addRow(null, ['tblHeader' => true]);
$revTable->addCell(1000, $firstRowStyle)->addText('Versi', $fontTblHeader, ['alignment' => Jc::CENTER]);
$revTable->addCell(1500, $firstRowStyle)->addText('Tanggal', $fontTblHeader, ['alignment' => Jc::CENTER]);
$revTable->addCell(2000, $firstRowStyle)->addText('Penyusun', $fontTblHeader);
$revTable->addCell(5000, $firstRowStyle)->addText('Deskripsi Perubahan', $fontTblHeader);

$revData = [
    ['v0.1', '10 Sep 2026', 'Business Analyst', 'Draf inisial: Identifikasi masalah manual KAK, tujuan umum, dan pemetaan 160 placeholder Word template.'],
    ['v0.5', '16 Sep 2026', 'Software Architect', 'Penambahan arsitektur teknis: Evaluasi opsi cloud gratis (Render/Railway), integrasi LibreOffice headless, dan skema dual-mode (Database & Stateless JSON).'],
    ['v0.9', '20 Sep 2026', 'Lead Dev & BA', 'Penyusunan use case formal, Acceptance Criteria berformat Given-When-Then, dan matriks mitigasi risiko memory cloud.'],
    ['v1.0', '23 Sep 2026', 'Tim Gabungan', 'Dokumen final disetujui: Standarisasi seluruh Functional & Non-Functional Requirements, Wireframe, dan Jadwal Sprint.']
];

foreach ($revData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $revTable->addRow();
    $revTable->addCell(1000, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $revTable->addCell(1500, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[1], $fontTblCell, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $revTable->addCell(2000, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $revTable->addCell(5000, $bg)->addText($row[3], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 2. RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY)
// -------------------------------------------------------------
$section->addTitle('2. Ringkasan Eksekutif (Executive Summary)', 1);

addP($section, 'Penyusunan dokumen Kerangka Acuan Kegiatan (KAK) merupakan instrumen perencana anggaran dan operasional yang sangat krusial dalam siklus administrasi pemerintahan di lingkungan Kemenko PMK. Saat ini, penyusunan KAK masih mengandalkan proses manual di mana staf perencana menyalin dan mengetik ulang template Microsoft Word (.docx) resmi yang berukuran besar dan berisi lebih dari 160 titik isian (placeholder), mulai dari identitas program, latar belakang, strategi pencapaian, rincian biaya DIPA, hingga lampiran matriks Gender Analysis Pathway (GAP). Praktik manual ini menimbulkan kerentanan human error yang tinggi, seperti pergeseran format tata letak tabel kedinasan, hilangnya pasal/poin instruksi wajib, inkonsistensi penomoran anggaran, serta lamanya waktu penyusunan (memerlukan 4 hingga 6 jam per dokumen).');

addP($section, 'Sistem Digitalisasi KAK hadir sebagai solusi berbasis web modern yang dibangun di atas kerangka kerja Laravel dengan antarmuka interaktif multi-step wizard. Sistem ini memandu staf langkah demi langkah untuk mengisi seluruh parameter kegiatan secara terstruktur, memvalidasi integritas data, dan secara otomatis merekonstruksi dokumen KAK resmi yang formatnya 100% identik dengan template master instansi melalui mesin penggabung template PHPWord. Pengguna diberikan fleksibilitas untuk mengunduh output dalam format Microsoft Word (.docx) untuk penyesuaian lanjutan maupun Portable Document Format (PDF) melalui mesin konversi LibreOffice headless.');

addCallout($section, 'Nilai Strategis & Batasan Sumber Daya', 'Sistem ini dirancang dengan prinsip arsitektur perangkat lunak ultra-ringan (lean architecture) dan berbiaya nol (zero licensing cost). Sistem mampu beroperasi optimal pada infrastruktur cloud gratis (seperti Render / Koyeb free-tier) dengan efisiensi konsumsi memori di bawah 128 MB RAM serta mendukung mekanisme dual-mode (berjalan tanpa database menggunakan penyimpanan sesi terenkripsi atau dengan database SQLite/MySQL/PostgreSQL gratis).');

// -------------------------------------------------------------
// 3. LATAR BELAKANG & RUMUSAN MASALAH
// -------------------------------------------------------------
$section->addTitle('3. Latar Belakang & Rumusan Masalah', 1);

$section->addTitle('3.1. Kondisi Proses Bisnis Saat Ini (As-Is Process)', 2);
addP($section, 'Dalam tata kelola penganggaran tahunan dan pengajuan Rencana Kerja Pemerintah (RKP), setiap unit eselon II (Keasdepan) diwajibkan menyusun puluhan dokumen KAK untuk menaungi setiap sub-output kegiatan. Alur kerja yang berjalan saat ini adalah sebagai berikut:');

$section->addListItem('Staf perencana mengunduh file master Word (.docx) berformat baku dari drive bersama atau arsip tahun anggaran sebelumnya.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Staf melakukan pencarian manual terhadap teks petunjuk pengisian yang ditandai dengan tanda titik-titik ("……"), teks dalam kurung siku ("[......]"), atau blok warna kuning (yellow highlight).', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Staf mengisi rincian kegiatan satu per satu, menghitung alokasi anggaran, dan menyalin data dasar hukum secara manual.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('File diedit berulang kali oleh berbagai staf dan dikirimkan melalui aplikasi pesan instan atau email untuk diverifikasi oleh Pejabat Pembuat Komitmen (PPK) atau Atasan.', 0, $fontBody, ['spaceAfter' => 80]);

$section->addTitle('3.2. Identifikasi Permasalahan Utama', 2);
addP($section, 'Berdasarkan observasi lapangan dan wawancara dengan unit perencana, teridentifikasi 5 (lima) masalah utama yang menghambat produktivitas instansi:');

$probTable = $section->addTable($tblStyleBasic);
$probTable->addRow(null, ['tblHeader' => true]);
$probTable->addCell(800, $firstRowStyle)->addText('No', $fontTblHeader, ['alignment' => Jc::CENTER]);
$probTable->addCell(2500, $firstRowStyle)->addText('Gejala Masalah', $fontTblHeader);
$probTable->addCell(6200, $firstRowStyle)->addText('Dampak Operasional Terhadap Organisasi', $fontTblHeader);

$probData = [
    ['1', 'Kerusakan Format & Tata Letak (Layout Corruption)', 'Mengetik langsung pada tabel Word yang kompleks sering memicu pergeseran margin, pecahnya batas halaman (page break), rusaknya alignment logo kementerian, dan rusaknya struktur matriks GAP.'],
    ['2', 'Bagian Wajib Terlewat (Omission of Required Fields)', 'Dengan 160 titik isian yang tersebar di 15-20 halaman, staf kerap tidak sengaja melewatkan pengisian data krusial seperti dasar hukum terbaru, target sasaran gender, atau kode akun output.'],
    ['3', 'Inkonsistensi Redaksi & Kodefikasi', 'Penggunaan nomenklatur jabatan, kode RO/KRO, dan format angka rupiah berbeda-beda antar staf, menyulitkan kompilasi di tingkat Biro Perencanaan.'],
    ['4', 'Inefisiensi Waktu Kerja (High Labor Hours)', 'Rata-rata 4-6 jam kerja staf tersita hanya untuk formatting dokumen ketimbang memfokuskan waktu pada substansi analisis kebijakan pembangunan manusia.'],
    ['5', 'Tingginya Frekuensi Retur Revisi', 'Verifikator/PPK mengembalikan berkas rata-rata 3 hingga 5 kali revisi hanya karena cacat administratif format penulisan, memperlambat proses pencairan anggaran DIPA.']
];

foreach ($probData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $probTable->addRow();
    $probTable->addCell(800, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $probTable->addCell(2500, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $probTable->addCell(6200, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

$section->addTitle('3.3. Urgensi Digitalisasi', 2);
addP($section, 'Digitalisasi dokumen KAK menjadi prioritas mendesak dalam rangka memenuhi amanat Peraturan Presiden tentang Sistem Pemerintahan Berbasis Elektronik (SPBE), di mana standardisasi proses bisnis, akurasi dokumen perencanaan, dan efisiensi birokrasi menjadi indikator kinerja utama kementerian.');

// -------------------------------------------------------------
// 4. TUJUAN SISTEM (GOALS & OBJECTIVES)
// -------------------------------------------------------------
$section->addTitle('4. Tujuan Sistem (Goals & Objectives)', 1);

addP($section, 'Pengembangan Sistem Digitalisasi KAK memiliki target keberhasilan yang terbagi menjadi tujuan bisnis (organisasional) dan tujuan teknis (arsitektural), dengan parameter keberhasilan yang dapat diukur secara kuantitatif:');

$section->addTitle('4.1. Tujuan Bisnis (Business Goals)', 2);
$section->addListItem('Memangkas Durasi Penyusunan: Mengurangi waktu penyusunan dokumen KAK dari sebelumnya 4-6 jam menjadi kurang dari 30 menit (reduksi waktu kerja > 85%).', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Standarisasi Mutu 100%: Menghilangkan 100% cacat tata letak dan memastikan seluruh dokumen KAK yang dihasilkan di lingkungan deputi memiliki format, tipografi, dan margin yang presisi sesuai aturan tata naskah dinas.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Penurunan Angka Revisi Administratif: Mengurangi tingkat pengembalian draf KAK oleh Verifikator/PPK akibat kesalahan format penulisan hingga di bawah 10%.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Meningkatkan Kelengkapan Data: Memastikan tidak ada kolom wajib (seperti Kode Satker, Nomenklatur RO, Dasar Hukum, Analisis Gender) yang tertinggal melalui mekanisme validasi formulir terarah.', 0, $fontBody, ['spaceAfter' => 80]);

$section->addTitle('4.2. Tujuan Teknis (Technical Goals)', 2);
$section->addListItem('Kecepatan Generasi Dokumen: Menghasilkan dokumen Word (.docx) berukuran penuh (15+ halaman) dalam waktu < 2 detik sejak tombol ditekan.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Kecepatan Konversi PDF: Melakukan konversi format DOCX ke PDF melalui LibreOffice headless dalam waktu < 6 detik.', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Efisiensi Sumber Daya Cloud: Berjalan stabil pada platform cloud gratis dengan alokasi RAM maksimal 512 MB dan batas CPU 0.5 vCPU tanpa insiden kehabisan memori (Out-Of-Memory / OOM).', 0, $fontBody, ['spaceAfter' => 40]);
$section->addListItem('Zero-Data-Loss Wizard: Memastikan data isian pengguna yang panjang tidak hilang ketika terjadi pemadaman listrik atau penutupan browser yang tidak disengaja melalui penyimpanan draf otomatis berbasis sesi lokal/server.', 0, $fontBody, ['spaceAfter' => 80]);

// -------------------------------------------------------------
// 5. RUANG LINGKUP SISTEM (SCOPE)
// -------------------------------------------------------------
$section->addTitle('5. Ruang Lingkup Sistem (Scope)', 1);

addP($section, 'Batasan ruang lingkup disusun secara tegas untuk menjaga fokus pengembangan pada target fungsionalitas utama dan memastikan implementasi dapat diselesaikan tepat waktu sesuai batasan sumber daya:');

$scopeTable = $section->addTable($tblStyleBasic);
$scopeTable->addRow(null, ['tblHeader' => true]);
$scopeTable->addCell(4750, $firstRowStyle)->addText('Dalam Ruang Lingkup (In-Scope — Versi 1.0)', $fontTblHeader);
$scopeTable->addCell(4750, $firstRowStyle)->addText('Di Luar Ruang Lingkup (Out-of-Scope — Future)', $fontTblHeader);

$scopeData = [
    [
        'Modul Parsing Template Word: CLI command Artisan (kak:prepare-template) untuk mengekstrak dan memetakan 160 placeholder template master menjadi tag ${field_xxx}.',
        'Tanda Tangan Elektronik Bersertifikasi (BSrE): Sistem tidak mengintegrasikan sertifikat digital kriptografi; tanda tangan disediakan dalam format placeholder basah / manual.'
    ],
    [
        'Formulir Multi-Step Wizard: 7 seksi formulir dinamis (Identitas KAK, Latar Belakang & Hukum, Penerima Manfaat, Strategi Pencapaian, Waktu, Anggaran, Tanda Tangan & GAP).',
        'Integrasi API Otomatis dengan Sistem Eksternal: Tidak terhubung langsung via API dengan SAKTI Kemenkeu, SIPD Kemendagri, atau SPAN pada rilis v1.0.'
    ],
    [
        'Penyimpanan Draf Otomatis: Sinkronisasi data form per langkah ke dalam session server / local storage sehingga pengguna dapat berpindah langkah tanpa kehilangan data.',
        'Workflow Approval Birokrasi Berjenjang: Sistem tidak menangani alur disposisi approval bertingkat (Staf -> Kasubag -> Asdep -> Deputi); fokus pada penyusunan dokumen.'
    ],
    [
        'Mesin Generasi Dokumen Ganda (Dual-Engine): Pembuatan file Word (.docx) native via PHPWord dan konversi PDF via LibreOffice headless.',
        'Fitur Kolaborasi Real-time Multi-User: Sistem tidak mendukung editing bersamaan ala Google Docs pada satu draf form.'
    ],
    [
        'Regenerasi Format Instan: Kemampuan mengubah format output dari Word ke PDF atau sebaliknya untuk submission yang sudah tersimpan tanpa mengulang input data.',
        'Aplikasi Mobile Native: Sistem berupa Web Responsif (PWA ready), tidak dirilis sebagai aplikasi Android/iOS native di Play Store.'
    ],
    [
        'Modul Riwayat Pengajuan (Submission History): Daftar dokumen yang pernah dibuat dengan status, tanggal, tombol download, dan tombol generate ulang.',
        'Multi-Language (Bilingual): Antarmuka dan template dokumen secara eksklusif menggunakan Bahasa Indonesia kedinasan.'
    ]
];

foreach ($scopeData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $scopeTable->addRow();
    $scopeTable->addCell(4750, $bg)->addText('✓ ' . $row[0], $fontTblCell, ['spaceAfter' => 30]);
    $scopeTable->addCell(4750, $bg)->addText('✗ ' . $row[1], $fontTblCell, ['spaceAfter' => 30]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 6. TARGET PENGGUNA & PERSONA
// -------------------------------------------------------------
$section->addTitle('6. Target Pengguna & Persona', 1);

addP($section, 'Sistem ini dirancang untuk melayani 3 (tiga) peran pengguna dengan karakteristik, kebutuhan, dan kendala operasional yang spesifik:');

$personaTable = $section->addTable($tblStyleBasic);
$personaTable->addRow(null, ['tblHeader' => true]);
$personaTable->addCell(1800, $firstRowStyle)->addText('Atribut Persona', $fontTblHeader);
$personaTable->addCell(3800, $firstRowStyle)->addText('Persona 1: Staf Penyusun KAK', $fontTblHeader);
$personaTable->addCell(3900, $firstRowStyle)->addText('Persona 2: Verifikator / Atasan (PPK/Asdep)', $fontTblHeader);

$personaData = [
    ['Profil & Peran', 'Staf Perencana / Analis Kebijakan Pertama yang bertugas menyusun substansi teknis KAK setiap awal tahun anggaran.', 'Pejabat Pembuat Komitmen (PPK) atau Asisten Deputi yang bertanggung jawab memvalidasi akurasi dan menyetujui anggaran.'],
    ['Tanggung Jawab Utama', 'Mengumpulkan dasar hukum, merumuskan narasi latar belakang, menghitung rincian RAB, dan mengisi matriks GAP.', 'Memeriksa kesesuaian KAK dengan DIPA, memastikan format sesuai aturan tata naskah dinas, dan menandatangani dokumen final.'],
    ['Pain Points (Kendala)', 'Frustrasi karena file Word sering berantakan saat menambah baris tabel; sering ditegur karena lupa mengisi pasal tertentu; waktu habis untuk urusan teknis pengetikan.', 'Lelah memeriksa berlembar-lembar draf yang formatnya tidak seragam; sering mendapati tabel bergeser dan salah rumus; butuh waktu cepat untuk review.'],
    ['Kebutuhan Sistem', 'Formulir wizard yang jelas; input field yang terstruktur (teks panjang otomatis textarea, kode otomatis pendek); auto-save draf agar aman; unduh Word untuk sentuhan akhir.', 'File hasil generasi berupa PDF siap cetak yang rapi dan terstandarisasi; jaminan 100% kelengkapan struktur KAK; riwayat pengajuan dokumen teratur.'],
    ['Ekspektasi UX', 'Antarmuka sederhana, responsif di laptop dinas, navigasi antar-step yang tidak lambat, tidak membutuhkan pelatihan teknis yang rumit.', 'Tombol unduh PDF langsung dapat dibuka di HP atau tablet tanpa masalah layout bergeser.']
];

foreach ($personaData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $personaTable->addRow();
    $personaTable->addCell(1800, array_merge($bg, ['bgColor' => 'F1F5F9']))->addText($row[0], $fontTblCellB, ['spaceAfter' => 0]);
    $personaTable->addCell(3800, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $personaTable->addCell(3900, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

addP($section, 'Catatan Persona Tambahan (Administrator IT): Bertanggung jawab memantau ketersediaan aplikasi pada platform cloud gratis, memelihara template master KAK saat terjadi perubahan regulasi, serta memantau log sistem dan kapasitas ruang disk sementara.');

// -------------------------------------------------------------
// 7. USER STORIES & USE CASE
// -------------------------------------------------------------
$section->addTitle('7. User Stories & Use Case', 1);

$section->addTitle('7.1. Daftar User Stories', 2);
addP($section, 'Kebutuhan sistem dirumuskan ke dalam format standar Agile User Story (Sebagai [Peran], saya ingin [Aksi], sehingga [Manfaat]):');

$usTable = $section->addTable($tblStyleBasic);
$usTable->addRow(null, ['tblHeader' => true]);
$usTable->addCell(900, $firstRowStyle)->addText('ID', $fontTblHeader, ['alignment' => Jc::CENTER]);
$usTable->addCell(1600, $firstRowStyle)->addText('Sebagai [Peran]', $fontTblHeader);
$usTable->addCell(3500, $firstRowStyle)->addText('Saya Ingin [Aksi / Fitur]', $fontTblHeader);
$usTable->addCell(3500, $firstRowStyle)->addText('Sehingga [Manfaat / Nilai Bisnis]', $fontTblHeader);

$usData = [
    ['US-01', 'Staf Penyusun', 'Mengisi data KAK melalui formulir wizard bertahap yang dikelompokkan per topik', 'Saya tidak kewalahan melihat ratusan isian sekaligus dan dapat fokus pada satu bagian dalam satu waktu.'],
    ['US-02', 'Staf Penyusun', 'Sistem secara otomatis menyimpan isian saya di setiap perpindahan langkah (step)', 'Data yang sudah saya ketik tidak hilang saat koneksi internet terputus atau tab browser tidak sengaja tertutup.'],
    ['US-03', 'Staf Penyusun', 'Memilih format berkas unduhan akhir antara Word (.docx) atau PDF (.pdf)', 'Saya bisa memilih Word jika masih ingin mengedit manual bersama tim, atau PDF jika dokumen siap diajukan ke atasan.'],
    ['US-04', 'Staf Penyusun', 'Melihat ringkasan data isian (summary preview) sebelum menekan tombol generate', 'Saya dapat melakukan pemeriksaan akhir (final review) untuk memastikan tidak ada kesalahan ketik nama atau angka.'],
    ['US-05', 'Verifikator', 'Mengunduh berkas KAK dalam format PDF berstandar tinggi yang tata letaknya identik dengan master resmi', 'Saya dapat langsung memeriksa dan mencetak dokumen resmi tanpa risiko format tabel bergeser.'],
    ['US-06', 'Staf / Atasan', 'Melihat daftar riwayat dokumen KAK yang pernah diajukan sebelumnya di halaman submission', 'Saya dapat menelusuri kembali arsip dokumen KAK yang telah disusun tanpa harus mencari file di komputer pribadi.'],
    ['US-07', 'Staf Penyusun', 'Melakukan regenerasi dokumen yang sudah tersimpan ke format yang berbeda hanya dalam satu klik', 'Saya tidak perlu mengisi ulang formulir dari awal hanya untuk mendapatkan versi PDF dari draf Word yang sudah ada.'],
    ['US-08', 'Admin Sistem', 'Memperbarui file template master Word melalui command CLI sederhana', 'Sistem dapat segera menyesuaikan struktur field tanpa perlu menulis ulang kode program aplikasi dari awal.']
];

foreach ($usData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $usTable->addRow();
    $usTable->addCell(900, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $usTable->addCell(1600, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $usTable->addCell(3500, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $usTable->addCell(3500, $bg)->addText($row[3], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

$section->addTitle('7.2. Deskripsi Tekstual Use Case Diagram', 2);
addP($section, 'Hubungan antara aktor dan kasus penggunaan (use case) dalam sistem dimodelkan secara tekstual sebagai berikut:');

$section->addListItem('Aktor Utama 1: Staf Penyusun KAK', 0, $fontBodyBold, ['spaceAfter' => 20]);
$section->addListItem('UC-01: Mengakses Antarmuka Formulir Multi-Step Wizard', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-02: Mengisi Parameter Seksi (Identitas, Latar Belakang, Anggaran, GAP)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-03: Menyimpan Draf Isian Progresif (Auto-save)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-04: Memilih Format Dokumen (Word .docx / PDF .pdf)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-05: Melakukan Finalisasi & Generasi Dokumen Resmi', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-06: Mengunduh Berkas Hasil Generasi via Endpoint Adaptif', 1, $fontBody, ['spaceAfter' => 40]);

$section->addListItem('Aktor Utama 2: Verifikator / Atasan (PPK / Asdep)', 0, $fontBodyBold, ['spaceAfter' => 20]);
$section->addListItem('UC-07: Mengakses Halaman Riwayat Pengajuan (Submission Index)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-08: Melihat Rincian Metadata Pengajuan KAK', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-09: Mengunduh Berkas KAK (PDF / Word)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-10: Memicu Regenerasi Format Dokumen (Switch Format)', 1, $fontBody, ['spaceAfter' => 40]);

$section->addListItem('Aktor Pendukung: Administrator Sistem (SPBE Operator)', 0, $fontBodyBold, ['spaceAfter' => 20]);
$section->addListItem('UC-11: Menjalankan Template Preparation Command (php artisan kak:prepare-template)', 1, $fontBody, ['spaceAfter' => 20]);
$section->addListItem('UC-12: Memantau Log Sistem dan Kebersihan Storage Direktori Sementara', 1, $fontBody, ['spaceAfter' => 80]);

// -------------------------------------------------------------
// 8. KEBUTUHAN FUNGSIONAL (FUNCTIONAL REQUIREMENTS)
// -------------------------------------------------------------
$section->addTitle('8. Kebutuhan Fungsional (Functional Requirements)', 1);

addP($section, 'Kebutuhan fungsional sistem diberi nomor identifikasi unik, dikelompokkan berdasarkan modul kerja, dan diprioritaskan menggunakan metode MoSCoW (Must Have, Should Have, Could Have, Won\'t Have):');

$frTable = $section->addTable($tblStyleBasic);
$frTable->addRow(null, ['tblHeader' => true]);
$frTable->addCell(900, $firstRowStyle)->addText('ID', $fontTblHeader, ['alignment' => Jc::CENTER]);
$frTable->addCell(1800, $firstRowStyle)->addText('Modul', $fontTblHeader);
$frTable->addCell(5300, $firstRowStyle)->addText('Deskripsi Kebutuhan Fungsional', $fontTblHeader);
$frTable->addCell(1500, $firstRowStyle)->addText('Prioritas', $fontTblHeader, ['alignment' => Jc::CENTER]);

$frData = [
    ['FR-01', 'Template Engine', 'Sistem harus mampu mem-parsing template master Word (.docx), mengidentifikasi placeholder (titik-titik, highlight kuning), dan menggantinya dengan tag unik ${field_001} s/d ${field_160}.', 'Must Have'],
    ['FR-02', 'Template Engine', 'Sistem harus mengekstrak metadata field dari template menjadi file konfigurasi resources/templates/field_map.json secara terstruktur.', 'Must Have'],
    ['FR-03', 'Wizard Input', 'Sistem harus menyediakan formulir multi-step wizard yang membagi isian ke dalam 7 seksi logis sesuai urutan resmi KAK.', 'Must Have'],
    ['FR-04', 'Wizard Input', 'Sistem harus secara otomatis merender tipe elemen input yang sesuai (textarea untuk teks narasi > 100 karakter, input text biasa untuk isian singkat/kode).', 'Must Have'],
    ['FR-05', 'Draft Session', 'Sistem harus menyimpan draf isian form ke dalam session secara otomatis pada setiap perpindahan langkah tanpa me-reload seluruh halaman.', 'Must Have'],
    ['FR-06', 'Validasi Form', 'Sistem harus memvalidasi data wajib (required) pada client-side (Alpine.js) dan server-side (Laravel FormRequest) sebelum dokumen di-generate.', 'Must Have'],
    ['FR-07', 'Generasi DOCX', 'Sistem harus mampu memetakan seluruh isian pengguna ke template merge-field menggunakan PHPWord TemplateProcessor dan menghasilkan file DOCX yang valid.', 'Must Have'],
    ['FR-08', 'Konversi PDF', 'Sistem harus mampu mengonversi dokumen DOCX ke PDF menggunakan perintah headless LibreOffice (soffice) di dalam lingkungan kontainer cloud.', 'Must Have'],
    ['FR-09', 'Pilihan Format', 'Sistem harus menyediakan radio button / selector bagi pengguna untuk memilih format akhir dokumen (DOCX atau PDF) pada langkah finalisasi.', 'Must Have'],
    ['FR-10', 'Manajemen Riwayat', 'Sistem harus menyimpan riwayat submission ke database (kak_submissions) mencakup judul, format, data JSON, dan path file.', 'Must Have'],
    ['FR-11', 'Adaptive Download', 'Sistem harus menyediakan satu route unduh (/submissions/{id}/download) yang secara otomatis mendeteksi MIME type file fisik (.docx atau .pdf).', 'Must Have'],
    ['FR-12', 'Regenerasi Format', 'Sistem harus menyediakan fitur regenerasi untuk mengubah format file submission yang sudah tersimpan tanpa meminta pengguna mengetik ulang data.', 'Should Have'],
    ['FR-13', 'Arsitektur Stateless', 'Sistem harus mendukung opsi berjalan tanpa database (stateless/session-only) untuk skenario deployment minimalis atau hosting statis.', 'Should Have'],
    ['FR-14', 'Auto-Cleanup File', 'Sistem harus secara otomatis menghapus file Word sementara hasil konversi PDF setelah file PDF selesai dibentuk guna menghemat disk.', 'Should Have'],
    ['FR-15', 'Fallback Generator', 'Jika konversi LibreOffice gagal (timeout atau biner tidak ditemukan), sistem harus secara aman memberikan fallback berupa file DOCX asli dengan notifikasi informatif.', 'Should Have']
];

foreach ($frData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $frTable->addRow();
    $frTable->addCell(900, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $frTable->addCell(1800, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $frTable->addCell(5300, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $prioColor = ($row[3] === 'Must Have') ? '2B6CB0' : '4A5568';
    $frTable->addCell(1500, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[3], ['name' => 'Calibri', 'size' => 9, 'bold' => true, 'color' => $prioColor], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 9. KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)
// -------------------------------------------------------------
$section->addTitle('9. Kebutuhan Non-Fungsional (Non-Functional Requirements)', 1);

addP($section, 'Kebutuhan non-fungsional mendefinisikan batasan teknis, kualitas layanan, dan keandalan sistem agar mampu beroperasi stabil dalam ekosistem infrastruktur gratis/minim biaya:');

$nfrTable = $section->addTable($tblStyleBasic);
$nfrTable->addRow(null, ['tblHeader' => true]);
$nfrTable->addCell(1000, $firstRowStyle)->addText('ID', $fontTblHeader, ['alignment' => Jc::CENTER]);
$nfrTable->addCell(2000, $firstRowStyle)->addText('Kategori', $fontTblHeader);
$nfrTable->addCell(6500, $firstRowStyle)->addText('Spesifikasi Batasan & Parameter Kinerja', $fontTblHeader);

$nfrData = [
    ['NFR-01', 'Performa Generasi DOCX', 'Waktu pembuatan dokumen Word (.docx) tidak boleh melebihi 2.5 detik pada beban rata-rata, dengan konsumsi memori puncak (peak memory) di bawah 64 MB RAM.'],
    ['NFR-02', 'Performa Konversi PDF', 'Waktu konversi DOCX ke PDF via LibreOffice headless tidak boleh melebihi 7 detik, dengan alokasi process timeout sebesar maksimal 30 detik untuk mencegah zombie process.'],
    ['NFR-03', 'Keamanan (Sanitasi Input)', 'Seluruh nilai input harus disanitasi dari entitas XML ilegal (seperti ampersand "&" tidak ter-escape, karakter kontrol) untuk mencegah korupsi struktur OpenXML (word/document.xml).'],
    ['NFR-04', 'Keamanan (Proteksi File)', 'File .env, file template master, dan storage privat tidak boleh dapat diakses langsung via URL browser (harus berada di luar webroot public_html).'],
    ['NFR-05', 'Kompatibilitas Dokumen', 'File .docx hasil generasi harus 100% kompatibel dan dapat dibuka tanpa pesan error perbaikan pada Microsoft Word versi 2013, 2016, 2019, 2021, Office 365, serta LibreOffice Writer 7.x ke atas.'],
    ['NFR-06', 'Kompatibilitas Browser', 'Antarmuka web harus beroperasi sempurna tanpa degradasi fungsi pada browser modern standar: Google Chrome, Microsoft Edge, Mozilla Firefox, dan Apple Safari versi 2 tahun terakhir.'],
    ['NFR-07', 'Ketersediaan (Hosting Gratis)', 'Sistem harus memiliki penanganan graceful terhadap kondisi "Cold Start" pada penyedia hosting gratis (seperti Render/Koyeb yang mematikan instance setelah 15 menit tidak aktif), dengan batas bangun kembali < 50 detik.'],
    ['NFR-08', 'Efisiensi Disk Storage', 'Penggunaan disk pada kontainer cloud harus di bawah 500 MB (termasuk biner LibreOffice minimalis alpine/debian-slim). File output sementara harus memiliki mekanisme rotasi otomatis.']
];

foreach ($nfrData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $nfrTable->addRow();
    $nfrTable->addCell(1000, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $nfrTable->addCell(2000, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $nfrTable->addCell(6500, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 10. ALUR SISTEM (SYSTEM FLOW)
// -------------------------------------------------------------
$section->addTitle('10. Alur Sistem (System Flow)', 1);

$section->addTitle('10.1. Penjelasan Alur End-to-End Naratif', 2);
addP($section, 'Alur kerja sistem berjalan secara terintegrasi dari inisialisasi hingga berkas terunduh oleh pengguna:');

addP($section, '1. Tahap Inisialisasi: Pengguna (Staf Penyusun) mengakses aplikasi melalui browser pada halaman dashboard / submissions. Jika ingin menyusun KAK baru, pengguna menekan tombol "Buat KAK Baru" dan diarahkan ke rute wizard (/wizard). Sistem membaca konfigurasi pemetaan field dari file JSON untuk merender formulir seksi pertama.');

addP($section, '2. Tahap Pengisian Bertahap (Multi-Step): Pengguna mengisi field-field yang telah dikelompokkan ke dalam 7 seksi logis. Setiap kali pengguna menekan tombol "Lanjut ke Seksi Berikutnya", data pada seksi aktif dikirimkan via asynchronous request (atau disimpan ke sesi formulir). Komponen Alpine.js mengelola status antarmuka secara lokal, sementara backend memvalidasi tipe data yang masuk.');

addP($section, '3. Tahap Finalisasi & Pemilihan Format: Pada seksi ke-7 (terakhir), pengguna melihat rangkuman kelengkapan isian dan disajikan komponen pemilihan format berkas (Radio button: "Microsoft Word (.docx)" atau "Adobe PDF (.pdf)"). Pengguna menekan tombol "Simpan & Generate Dokumen".');

addP($section, '4. Tahap Pemrosesan Dokumen (Backend Engine):');
$section->addListItem('Backend Laravel memuat template master yang telah disiapkan (resources/templates/kak_template_merged.docx).', 0, $fontBody, ['spaceAfter' => 30]);
$section->addListItem('Kelas KakDocumentGenerator memanggil PhpOffice\PhpWord\TemplateProcessor untuk menggantikan seluruh placeholder ${field_xxx} dengan nilai isian pengguna. Seluruh karakter khusus seperti ampersand (&) disanitasi otomatis menjadi bentuk aman XML (&amp;).', 0, $fontBody, ['spaceAfter' => 30]);
$section->addListItem('File .docx sementara disimpan di storage/app/output/.', 0, $fontBody, ['spaceAfter' => 30]);
$section->addListItem('Apabila pengguna memilih format PDF, sistem mengeksekusi proses subprocess asinkron memanggil biner LibreOffice: "soffice --headless --convert-to pdf --outdir [path_output] [path_docx]". Sistem memantau exit code. Jika sukses, file .docx sementara dihapus dan file .pdf dipertahankan.', 0, $fontBody, ['spaceAfter' => 30]);
$section->addListItem('Metadata submission (Judul KAK, seluruh payload data JSON, format output, dan path berkas fisik) disimpan ke tabel database kak_submissions.', 0, $fontBody, ['spaceAfter' => 60]);

addP($section, '5. Tahap Unduh Dokumen: Sistem me-redirect pengguna ke halaman ringkasan sukses (/submissions/{id}) yang menampilkan preview metadata dan tombol unduh. Saat tautan /submissions/{id}/download diakses, controller membaca file fisik, mengatur header respons HTTP resmi (Content-Type application/vnd.openxmlformats... atau application/pdf) dan memicu dialog simpan file pada browser pengguna.');

$section->addTitle('10.2. Representasi Langkah Alur Logis (Flowchart Logic)', 2);
addP($section, 'Secara algoritmik, alur proses dapat digambarkan dalam langkah-langkah terstruktur berikut:');

$flowTable = $section->addTable($tblStyleBasic);
$flowTable->addRow(null, ['tblHeader' => true]);
$flowTable->addCell(1000, $firstRowStyle)->addText('Langkah', $fontTblHeader, ['alignment' => Jc::CENTER]);
$flowTable->addCell(2500, $firstRowStyle)->addText('Entitas Pelaksana', $fontTblHeader);
$flowTable->addCell(6000, $firstRowStyle)->addText('Aktivitas Logika / Keputusan Sistem', $fontTblHeader);

$flowData = [
    ['L-01', 'Pengguna / Browser', 'Mengakses route /wizard dan memulai pengisian pada Seksi 1 (Identitas Program).'],
    ['L-02', 'Frontend (Alpine.js)', 'Menyimpan input pengguna ke objek state lokal dan memvalidasi kelengkapan field wajib.'],
    ['L-03', 'Frontend & Session', 'Menekan tombol "Selanjutnya" -> data diposting ke session server (/wizard/save-draft).'],
    ['L-04', 'Pengguna', 'Mengulangi pengisian hingga Seksi 7, lalu memilih format berkas akhir: [DOCX] atau [PDF].'],
    ['L-05', 'Backend (Controller)', 'Menerima submission payload -> Memeriksa integritas validasi FormRequest.'],
    ['L-06', 'PHPWord Engine', 'Membuka template merged -> Loop substitusi setValue() untuk 160 tag -> Simpan file DOCX.'],
    ['L-07', 'Percabangan Logika', 'Kondisi: Apakah format pilihan == "PDF"?\n- JIKA YA: Lanjut ke L-08 (Konversi LibreOffice).\n- JIKA TIDAK: Langsung lompat ke L-10 (Selesai DOCX).'],
    ['L-08', 'Subprocess LibreOffice', 'Eksekusi: soffice --headless --convert-to pdf. Cek exit code proses.'],
    ['L-09', 'Error Handling Branch', 'Kondisi: Apakah konversi PDF berhasil?\n- JIKA SUKSES: Hapus DOCX temporer, catat path PDF.\n- JIKA GAGAL: Tangkap exception, set fallback format = "docx", catat error log.'],
    ['L-10', 'Database Persistence', 'Simpan rekaman ke kak_submissions -> Kembalikan ID pengajuan ke frontend.'],
    ['L-11', 'Adaptive Downloader', 'Pengguna klik unduh -> Controller membaca stream file -> Pengiriman respons attachment binary.']
];

foreach ($flowData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $flowTable->addRow();
    $flowTable->addCell(1000, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $flowTable->addCell(2500, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $flowTable->addCell(6000, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 11. ARSITEKTUR TEKNIS (TECHNICAL OVERVIEW)
// -------------------------------------------------------------
$section->addTitle('11. Arsitektur Teknis (Technical Overview)', 1);

$section->addTitle('11.1. Komposisi Perangkat Lunak (Tech Stack)', 2);
addP($section, 'Arsitektur sistem dibangun menggunakan komponen open-source teruji tanpa lisensi berbayar:');

$techTable = $section->addTable($tblStyleBasic);
$techTable->addRow(null, ['tblHeader' => true]);
$techTable->addCell(2500, $firstRowStyle)->addText('Lapisan Arsitektur', $fontTblHeader);
$techTable->addCell(3000, $firstRowStyle)->addText('Teknologi / Pustaka', $fontTblHeader);
$techTable->addCell(4000, $firstRowStyle)->addText('Alasan Pemilihan & Fungsi Spesifik', $fontTblHeader);

$techData = [
    ['Backend Framework', 'Laravel 11.x (PHP 8.3)', 'Framework PHP modern dengan ekosistem kuat, routing ekspresif, manajemen session aman, dan dukungan Eloquent ORM fleksibel.'],
    ['Document Template Engine', 'PhpOffice/PhpWord 1.4.x', 'Pustaka standar industri untuk manipulasi dokumen OpenXML (.docx) dengan TemplateProcessor yang efisien dalam mengganti tag merge field.'],
    ['PDF Conversion Engine', 'LibreOffice Headless (soffice)', 'Mesin rendering open-source berstandar internasional yang menjamin hasil konversi PDF memiliki tata letak 100% identik dengan Word aslinya.'],
    ['Frontend Styling', 'Tailwind CSS 3.4', 'Utility-first CSS framework untuk menghasilkan antarmuka pemerintah yang modern, bersih, responsif, dan ringan tanpa dependensi berat.'],
    ['Frontend Reactivity', 'Alpine.js 3.x', 'Framework JavaScript reaktif ultra-ringan (±15 KB) untuk menangani multi-step wizard, navigasi tab, dan validasi tanpa overhead Vue/React.'],
    ['Database Layer', 'MySQL / SQLite / PostgreSQL', 'Dukungan multi-driver. SQLite digunakan untuk mode portabel mandiri, sedangkan MySQL/Postgres digunakan untuk deployment cloud persisten.']
];

foreach ($techData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $techTable->addRow();
    $techTable->addCell(2500, array_merge($bg, ['bgColor' => 'F1F5F9']))->addText($row[0], $fontTblCellB, ['spaceAfter' => 0]);
    $techTable->addCell(3000, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $techTable->addCell(4000, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

$section->addTitle('11.2. Skema Data (Database Schema & Stateless Option)', 2);
addP($section, 'Sistem dirancang dengan fleksibilitas penyimpanan. Untuk deployment reguler, sistem menggunakan tabel tunggal berkinerja tinggi (kak_submissions). Untuk deployment minimalis tanpa database, sistem menggunakan enkripsi session/file JSON:');

$schemaTable = $section->addTable($tblStyleBasic);
$schemaTable->addRow(null, ['tblHeader' => true]);
$schemaTable->addCell(2000, $firstRowStyle)->addText('Nama Kolom', $fontTblHeader);
$schemaTable->addCell(1500, $firstRowStyle)->addText('Tipe Data', $fontTblHeader);
$schemaTable->addCell(1500, $firstRowStyle)->addText('Atribut', $fontTblHeader);
$schemaTable->addCell(4500, $firstRowStyle)->addText('Deskripsi & Contoh Nilai', $fontTblHeader);

$schemaData = [
    ['id', 'BIGINT UNSIGNED', 'PK, Auto Increment', 'Pengidentifikasi unik setiap draf/dokumen KAK yang diajukan.'],
    ['judul', 'VARCHAR(255)', 'Nullable, Indexed', 'Judul kegiatan KAK (misal: "Rekomendasi Kebijakan Akses Layanan Ramah Anak").'],
    ['data', 'JSON', 'Nullable', 'Struktur payload lengkap berisi 160 pasangan key-value field isian pengguna.'],
    ['status', 'VARCHAR(50)', 'Default: "draft"', 'Status dokumen: "draft", "generated", atau "archived".'],
    ['output_format', 'VARCHAR(10)', 'Default: "docx"', 'Pilihan format berkas terakhir yang di-generate ("docx" atau "pdf").'],
    ['generated_file_path', 'VARCHAR(255)', 'Nullable', 'Path relatif file hasil simpan di disk lokal (contoh: "output/kak_sub_1_2026.docx").'],
    ['created_at', 'TIMESTAMP', 'Nullable', 'Waktu pencatatan dokumen pertama kali dibuat.'],
    ['updated_at', 'TIMESTAMP', 'Nullable', 'Waktu terakhir pembaruan data atau regenerasi berkas.']
];

foreach ($schemaData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $schemaTable->addRow();
    $schemaTable->addCell(2000, array_merge($bg, ['bgColor' => 'F8FAFC']))->addText($row[0], $fontTblCellCode, ['spaceAfter' => 0]);
    $schemaTable->addCell(1500, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $schemaTable->addCell(1500, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $schemaTable->addCell(4500, $bg)->addText($row[3], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

addCallout($section, 'Opsi Arsitektur Tanpa Basis Data (Stateless Scenario)', 'Untuk skenario di mana sistem dihosting pada server tanpa alokasi basis data (seperti hosting statis atau lingkungan container ephemeral murni), sistem menyediakan konfigurasi STORAGE_DRIVER=session. Pada mode ini, seluruh data 160 field disimpan dalam cookie sesi terenkripsi atau file JSON lokal storage/app/drafts/{session_id}.json. Dokumen langsung di-stream ke browser pengguna saat selesai digenerate tanpa meninggalkan jejak basis data.');

$section->addTitle('11.3. Analisis Hosting Gratis (Free-Tier Infrastructure Options)', 2);
addP($section, 'Untuk memenuhi kriteria biaya nol (zero-budget project), dilakukan evaluasi komparatif terhadap 3 (tiga) penyedia layanan cloud gratis terkemuka:');

$hostTable = $section->addTable($tblStyleBasic);
$hostTable->addRow(null, ['tblHeader' => true]);
$hostTable->addCell(1800, $firstRowStyle)->addText('Platform Hosting', $fontTblHeader);
$hostTable->addCell(2200, $firstRowStyle)->addText('Alokasi Free Tier', $fontTblHeader);
$hostTable->addCell(3000, $firstRowStyle)->addText('Batasan Teknis (Constraints)', $fontTblHeader);
$hostTable->addCell(2500, $firstRowStyle)->addText('Rekomendasi & Kesimpulan', $fontTblHeader);

$hostData = [
    [
        'Render.com',
        'RAM 512 MB, 0.1 vCPU, Dukungan Native Dockerfile, SSL Otomatis.',
        'Instance "tidur" (sleep) setelah 15 menit tanpa trafik; cold start membutuhkan waktu 30-50 detik; disk bersifat ephemeral (reset saat restart).',
        'Sangat Direkomendasikan. Dockerfile bawaan project sudah siap pakai untuk menginstal PHP 8.3 & LibreOffice minimalis.'
    ],
    [
        'Koyeb.com',
        'RAM 512 MB, 0.1 vCPU, Micro instance gratis, 2 service aktif.',
        'Mirip Render (cold start), batasan bandwidth bulanan 55 GB, alokasi memori ketat saat eksekusi LibreOffice.',
        'Alternatif Utama. Sangat cocok sebagai instance cadangan (failover standby).'
    ],
    [
        'Railway.app',
        'Trial $5 kredit bulanan, eksekusi container sangat cepat, no cold start.',
        'Bukan sepenuhnya free-tier permanen (kredit bulanan terbatas); layanan akan mati jika kuota kredit terlampaui.',
        'Hanya untuk Pengujian Internal (QA/Staging). Kurang disarankan untuk penggunaan produksi berkelanjutan tanpa anggaran.'
    ]
];

foreach ($hostData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $hostTable->addRow();
    $hostTable->addCell(1800, array_merge($bg, ['bgColor' => 'F1F5F9']))->addText($row[0], $fontTblCellB, ['spaceAfter' => 0]);
    $hostTable->addCell(2200, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $hostTable->addCell(3000, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $hostTable->addCell(2500, $bg)->addText($row[3], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 12. WIREFRAME & STRUKTUR HALAMAN
// -------------------------------------------------------------
$section->addTitle('12. Wireframe & Struktur Antarmuka', 1);

addP($section, 'Sistem mengusung antarmuka yang bersih, intuitif, dan responsif dengan struktur navigasi yang terfokus pada kemudahan pengguna non-teknis:');

$wireTable = $section->addTable($tblStyleBasic);
$wireTable->addRow(null, ['tblHeader' => true]);
$wireTable->addCell(2200, $firstRowStyle)->addText('Halaman / Rute', $fontTblHeader);
$wireTable->addCell(2500, $firstRowStyle)->addText('Komponen Utama Antarmuka', $fontTblHeader);
$wireTable->addCell(4800, $firstRowStyle)->addText('Fungsi & Perilaku Pengguna (User Interaction)', $fontTblHeader);

$wireData = [
    [
        'Dashboard & Riwayat (/submissions)',
        'Header identitas Kemenko PMK, Tombol "Buat KAK Baru", Tabel arsip submission (ID, Judul, Format, Tanggal, Aksi).',
        'Menjadi titik masuk utama. Pengguna dapat melihat dokumen yang pernah dibuat, mengunduh ulang berkas yang tersimpan, atau memicu tombol regenerasi format.'
    ],
    [
        'Wizard Form Step 1: Identitas Program & Cover',
        'Progress bar seksi (Step 1 dari 7), input teks: Nama Asisten Deputi, Tahun Anggaran, Judul Kebijakan, Kode RO/KRO.',
        'Mengisi data muka dokumen. Terdapat placeholder panduan otomatis (misal: "Contoh: 4321.BMA.001"). Data otomatis divalidasi format tahunnya.'
    ],
    [
        'Wizard Form Step 2: Latar Belakang & Hukum',
        'Textarea ekspansif untuk Narasi Latar Belakang, Input list dasar hukum (UU, PP, Perpres), Urgensi Masalah.',
        'Menampung teks panjang. Textarea dilengkapi counter karakter dan auto-expand agar pengguna nyaman membaca narasi kebijakan.'
    ],
    [
        'Wizard Form Step 3: Penerima Manfaat',
        'Input sasaran penerima manfaat (Kementerian/Lembaga, Pemda, Masyarakat), jumlah target kuantitatif.',
        'Mendefinisikan siapa penerima dampak dari program koordinasi yang direncanakan.'
    ],
    [
        'Wizard Form Step 4: Strategi Pencapaian',
        'Pilihan metode pelaksanaan (Swakelola / Kontraktual), tahapan pelaksanaan (Persiapan, Pelaksanaan, Evaluasi).',
        'Menyusun milestone kerja dan tata cara operasionalisasi rekomendasi kebijakan.'
    ],
    [
        'Wizard Form Step 5: Waktu Pelaksanaan',
        'Input bulan mulai, bulan selesai, tabel matriks jadwal kegiatan bulanan (Januari - Desember).',
        'Menentukan linimasa eksekusi program sepanjang tahun anggaran berjalan.'
    ],
    [
        'Wizard Form Step 6: Rincian Biaya (RAB)',
        'Input total pagu anggaran DIPA, rincian komponen biaya, sumber dana APBN.',
        'Memasukkan alokasi finansial. Format angka diformat otomatis dengan pemisah ribuan rupiah.'
    ],
    [
        'Wizard Form Step 7: Tanda Tangan & GAP',
        'Input pejabat penandatangan (Nama, NIP, Jabatan), serta isian matriks Gender Analysis Pathway (GAP).',
        'Tahap penutup. Memastikan analisis responsif gender terisi sesuai pedoman Bappenas dan Kementerian PPPA.'
    ],
    [
        'Modal / Panel Pemilihan Format Output',
        'Card interaktif: Pilihan 1: Word (.docx) dengan icon Word; Pilihan 2: PDF (.pdf) dengan icon PDF; Tombol submit.',
        'Muncul di akhir Step 7. Pengguna memilih format yang diinginkan sebelum proses kompilasi dokumen dieksekusi backend.'
    ],
    [
        'Halaman Sukses & Unduh (/submissions/{id})',
        'Banner sukses hijau, ringkasan metadata KAK, Tombol unduh utama warna biru, Tombol regenerasi format lain.',
        'Menyajikan tautan unduh instan. Jika pengguna butuh format lain, dapat langsung menekan tombol regenerasi tanpa mengetik ulang.'
    ]
];

foreach ($wireData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $wireTable->addRow();
    $wireTable->addCell(2200, array_merge($bg, ['bgColor' => 'F1F5F9']))->addText($row[0], $fontTblCellB, ['spaceAfter' => 0]);
    $wireTable->addCell(2500, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $wireTable->addCell(4800, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 13. KRITERIA PENERIMAAN (ACCEPTANCE CRITERIA)
// -------------------------------------------------------------
$section->addTitle('13. Kriteria Penerimaan (Acceptance Criteria)', 1);

addP($section, 'Kriteria penerimaan didefinisikan menggunakan standar pengujian perilaku (Behavior-Driven Development / Given-When-Then) untuk menjamin validitas pengujian QA:');

$section->addTitle('13.1. Skenario AC-01: Navigasi Wizard & Penyimpanan Draf Otomatis', 2);
addP($section, 'GIVEN pengguna berada di halaman wizard Step 1 (Identitas Program) dan telah menginputkan data Asisten Deputi dan Tahun Anggaran;');
addP($section, 'WHEN pengguna menekan tombol "Lanjut ke Langkah 2";');
addP($section, 'THEN sistem berhasil menyimpan data Step 1 ke dalam session backend tanpa memuat ulang halaman secara penuh, dan antarmuka beralih ke Step 2 dengan progress bar menunjukkan angka 28% (2 dari 7).');

$section->addTitle('13.2. Skenario AC-02: Generasi Dokumen Format Microsoft Word (.docx)', 2);
addP($section, 'GIVEN pengguna telah melengkapi seluruh isian wajib pada 7 seksi wizard dan memilih radio button "Word (.docx)";');
addP($section, 'WHEN pengguna menekan tombol "Generate Dokumen KAK";');
addP($section, 'THEN backend mengganti seluruh 160 tag merge-field di template master dengan data isian pengguna, menyimpan file fisik di storage/app/output/, membuat entri di tabel kak_submissions, dan mengarahkan pengguna ke halaman unduh dalam waktu kurang dari 2.5 detik.');

$section->addTitle('13.3. Skenario AC-03: Generasi Dokumen Format PDF via LibreOffice', 2);
addP($section, 'GIVEN pengguna memilih format "PDF (.pdf)" dan menekan tombol "Generate Dokumen KAK";');
addP($section, 'WHEN backend selesai membentuk dokumen Word sementara dan mengeksekusi biner LibreOffice headless;');
addP($section, 'THEN dihasilkan file berkas berformat .pdf dengan layout, margin, dan tabel yang persis sama dengan dokumen Word, file DOCX sementara otomatis terhapus, dan tautan unduh menyajikan file dengan MIME type application/pdf.');

$section->addTitle('13.4. Skenario AC-04: Regenerasi Format Instan dari Riwayat Submission', 2);
addP($section, 'GIVEN dokumen KAK dengan ID #12 sebelumnya di-generate dalam format Word (.docx);');
addP($section, 'WHEN pengguna menekan tombol "Generate Ulang ke PDF" pada halaman ringkasan pengajuan;');
addP($section, 'THEN sistem mengambil payload JSON dari kolom kak_submissions.data, menjalankan proses konversi LibreOffice, memperbarui kolom output_format menjadi "pdf", dan menyajikan file PDF baru untuk diunduh tanpa meminta pengguna mengisi ulang formulir.');

$section->addTitle('13.5. Skenario AC-05: Penanganan Graceful Fallback Jika Mesin PDF Mengalami Kegagalan', 2);
addP($section, 'GIVEN pengguna memilih format PDF pada server lokal yang belum terinstal LibreOffice (soffice binary not found);');
addP($section, 'WHEN proses konversi PDF gagal dieksekusi;');
addP($section, 'THEN sistem tidak boleh menampilkan error 500 layar putih (white screen of death), melainkan otomatis mengamankan file Word (.docx) yang sudah berhasil dibentuk, menyajikan file Word tersebut kepada pengguna, dan menampilkan notifikasi peringatan: "Konversi PDF tidak tersedia pada server ini. Sistem menyajikan dokumen resmi dalam format Word (.docx)".');

// -------------------------------------------------------------
// 14. RISIKO & MITIGASI
// -------------------------------------------------------------
$section->addTitle('14. Risiko & Mitigasi (Risk Matrix)', 1);

addP($section, 'Analisis risiko proyek dilakukan secara proaktif untuk mengidentifikasi potensi kendala infrastruktur, keterbatasan teknis, dan langkah mitigasinya:');

$riskTable = $section->addTable($tblStyleBasic);
$riskTable->addRow(null, ['tblHeader' => true]);
$riskTable->addCell(800, $firstRowStyle)->addText('No', $fontTblHeader, ['alignment' => Jc::CENTER]);
$riskTable->addCell(2200, $firstRowStyle)->addText('Potensi Risiko Teknis', $fontTblHeader);
$riskTable->addCell(1300, $firstRowStyle)->addText('Dampak', $fontTblHeader, ['alignment' => Jc::CENTER]);
$riskTable->addCell(1200, $firstRowStyle)->addText('Peluang', $fontTblHeader, ['alignment' => Jc::CENTER]);
$riskTable->addCell(4000, $firstRowStyle)->addText('Strategi Mitigasi Terencana', $fontTblHeader);

$riskData = [
    [
        '1', 'Keterbatasan RAM Cloud Free-Tier (OOM Crash saat PDF)',
        'Tinggi', 'Sedang',
        'Menggunakan image Docker berbasis Debian/Alpine slim yang dioptimasi; menyetel LibreOffice dalam headless mode minimal tanpa Java (-env:UserInstallation); menetapkan timeout eksekusi 20 detik; menyediakan fallback otomatis ke DOCX jika RAM tertekan.'
    ],
    [
        '2', 'Kondisi Cold-Start Layanan Hosting Gratis (30-50s Delay)',
        'Sedang', 'Tinggi',
        'Menambahkan indikator loading interaktif pada frontend saat request awal berlangsung; menerapkan bot ping berkala (cron job uptime) setiap 14 menit agar instance cloud tetap dalam kondisi bangun (warm instance).'
    ],
    [
        '3', 'Karakter Khusus XML Merusak File DOCX (&, <, >)',
        'Tinggi', 'Sedang',
        'Menerapkan sanitasi input menyeluruh di kelas KakDocumentGenerator dengan mengonversi karakter reserved XML menjadi entitas aman (&amp;, &lt;, &gt;) sebelum diserahkan ke TemplateProcessor.'
    ],
    [
        '4', 'Perbedaan Font Rendering pada Server Linux (PDF Output)',
        'Sedang', 'Tinggi',
        'Menginstal paket font Microsoft standar (msttcorefonts / ttf-mscorefonts-installer) atau font substitusi metrik sempurna (Carlito, Liberation Sans) pada Dockerfile agar penataan spasi dokumen PDF 100% presisi dengan Word.'
    ],
    [
        '5', 'Perubahan Struktur Regulasi Format Template Master KAK',
        'Sedang', 'Rendah',
        'Memisahkan logika template ke dalam CLI command Artisan (kak:prepare-template). Tim teknis cukup meletakkan file .docx baru di folder template/ dan menjalankan ulang command tanpa merombak arsitektur kode aplikasi.'
    ]
];

foreach ($riskData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $riskTable->addRow();
    $riskTable->addCell(800, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $riskTable->addCell(2200, $bg)->addText($row[1], $fontTblCellB, ['spaceAfter' => 0]);
    $dampakColor = ($row[2] === 'Tinggi') ? 'C53030' : 'DD6B20';
    $riskTable->addCell(1300, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[2], ['name' => 'Calibri', 'size' => 9, 'bold' => true, 'color' => $dampakColor], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $riskTable->addCell(1200, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[3], $fontTblCell, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $riskTable->addCell(4000, $bg)->addText($row[4], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 15. TIMELINE & MILESTONE
// -------------------------------------------------------------
$section->addTitle('15. Timeline & Milestone Proyek', 1);

addP($section, 'Pelaksanaan proyek diestimasikan berlangsung selama 6 (enam) minggu kerja dengan pembagian siklus sprint dua mingguan (bi-weekly sprint) yang mencakup seluruh tahapan rekayasa perangkat lunak:');

$timeTable = $section->addTable($tblStyleBasic);
$timeTable->addRow(null, ['tblHeader' => true]);
$timeTable->addCell(1200, $firstRowStyle)->addText('Fase / Sprint', $fontTblHeader, ['alignment' => Jc::CENTER]);
$timeTable->addCell(1800, $firstRowStyle)->addText('Durasi & Waktu', $fontTblHeader);
$timeTable->addCell(3500, $firstRowStyle)->addText('Ruang Lingkup Kegiatan Utama', $fontTblHeader);
$timeTable->addCell(3000, $firstRowStyle)->addText('Keluaran / Deliverables', $fontTblHeader);

$timeData = [
    [
        'Fase 1: Analisis & Spesifikasi',
        'Minggu 1\n(5 Hari Kerja)',
        'Wawancara dengan staf perencana KAK, pembedahan template master Word 160 placeholder, penyusunan PRD, dan finalisasi skema field.',
        'Dokumen PRD resmi v1.0, file spesifikasi mapping, dan repositori Git proyek siap pakai.'
    ],
    [
        'Sprint 1: Core Engine & Parsing',
        'Minggu 2\n(5 Hari Kerja)',
        'Pengembangan artisan command (kak:prepare-template), ekstraksi field_map.json, implementasi dasar Laravel 11, dan integrasi PHPWord TemplateProcessor.',
        'Mesin generator Word (.docx) berfungsi via pengujian CLI; template master ter-tag.'
    ],
    [
        'Sprint 2: UI Wizard & Session',
        'Minggu 3\n(5 Hari Kerja)',
        'Membangun komponen antarmuka Tailwind CSS & Alpine.js, 7 seksi wizard, validasi input otomatis, dan mekanisme auto-save draf berbasis session.',
        'Antarmuka wizard formulir interaktif responsif; data tersimpan aman antar step.'
    ],
    [
        'Sprint 3: Headless PDF & DB',
        'Minggu 4\n(5 Hari Kerja)',
        'Integrasi konversi LibreOffice headless, konfigurasi Dockerfile cloud, migrasi database kak_submissions, dan fitur regenerasi dokumen.',
        'Fitur ganda (Word & PDF) aktif 100%; riwayat submission tersimpan di basis data.'
    ],
    [
        'Sprint 4: QA & Pengujian Mutu',
        'Minggu 5\n(5 Hari Kerja)',
        'Pengujian fungsional terpadu (Acceptance Testing), uji kompatibilitas MS Word 2013-365, uji stres memori cloud free-tier, perbaikan bug layout.',
        'Laporan hasil QA/QC; sistem bebas dari bug kritis dan aman dari memory leak.'
    ],
    [
        'Fase Penutup: UAT & Deployment',
        'Minggu 6\n(5 Hari Kerja)',
        'User Acceptance Testing (UAT) bersama calon pengguna di lingkungan deputi, penyusunan User Manual, dan deployment resmi ke hosting Render.com.',
        'Sistem beroperasi live di server produksi; berkas serah terima sistem selesai.'
    ]
];

foreach ($timeData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $timeTable->addRow();
    $timeTable->addCell(1200, array_merge($bg, ['alignment' => Jc::CENTER]))->addText($row[0], $fontTblCellB, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    $timeTable->addCell(1800, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
    $timeTable->addCell(3500, $bg)->addText($row[2], $fontTblCell, ['spaceAfter' => 0]);
    $timeTable->addCell(3000, $bg)->addText($row[3], $fontTblCellB, ['spaceAfter' => 0]);
}

$section->addTextBreak(1);

// -------------------------------------------------------------
// 16. LAMPIRAN (APPENDICES)
// -------------------------------------------------------------
$section->addTitle('16. Lampiran (Appendices)', 1);

$section->addTitle('16.1. Lampiran A: Contoh Struktur Konfigurasi field_map.json', 2);
addP($section, 'Berikut merupakan cuplikan representatif dari berkas konfigurasi pemetaan field (resources/templates/field_map.json) yang menjadi dasar rendering dinamis antarmuka formulir:');

$jsonBox = $section->addTable([
    'borderColor' => 'CBD5E0',
    'borderSize' => 6,
    'bgColor' => '2D3748',
    'cellMarginTop' => 100,
    'cellMarginBottom' => 100,
    'cellMarginLeft' => 140,
    'cellMarginRight' => 140,
    'width' => 100 * 50,
    'unit' => 'pct'
]);
$jsonBox->addRow();
$jsonCell = $jsonBox->addCell(9500);

$jsonSnippet = <<<'JSON'
[
  {
    "key": "field_001",
    "section": "1. Identitas Program & KAK",
    "context": "ASISTEN DEPUTI [......]",
    "label": "Nama Asisten Deputi (Cover)",
    "original_text": "……",
    "is_code": false,
    "placeholder": "Contoh: Pemenuhan Hak dan Perlindungan Anak",
    "is_textarea": false,
    "required": true
  },
  {
    "key": "field_002",
    "section": "1. Identitas Program & KAK",
    "context": "TAHUN [......]",
    "label": "Tahun Anggaran KAK",
    "original_text": "……",
    "is_code": false,
    "placeholder": "Contoh: 2026",
    "is_textarea": false,
    "required": true
  },
  {
    "key": "field_003",
    "section": "1. Identitas Program & KAK",
    "context": "REKOMENDASI ALTERNATIF KEBIJAKAN [......]",
    "label": "Judul / Topik Rekomendasi Kebijakan",
    "original_text": "……",
    "is_code": false,
    "placeholder": "Contoh: Rekomendasi Kebijakan Peningkatan Akses Layanan Ramah Anak di Wilayah Rawan Bencana",
    "is_textarea": true,
    "required": true
  }
]
JSON;

$lines = explode("\n", $jsonSnippet);
foreach ($lines as $ln) {
    $jsonCell->addText($ln, ['name' => 'Consolas', 'size' => 8.5, 'color' => 'A0AEC0'], ['spaceAfter' => 20]);
}

$section->addTextBreak(1);

$section->addTitle('16.2. Lampiran B: Glosarium Istilah Teknis & Bisnis (Glossary)', 2);
addP($section, 'Daftar definisi istilah teknis dan konsep perencanaan yang digunakan di dalam dokumen PRD ini:');

$glosTable = $section->addTable($tblStyleBasic);
$glosTable->addRow(null, ['tblHeader' => true]);
$glosTable->addCell(2500, $firstRowStyle)->addText('Istilah / Singkatan', $fontTblHeader);
$glosTable->addCell(7000, $firstRowStyle)->addText('Definisi & Konteks Penggunaan', $fontTblHeader);

$glosData = [
    ['KAK (Kerangka Acuan Kegiatan)', 'Dokumen perencanaan resmi instansi pemerintah yang memuat latar belakang, maksud dan tujuan, indikator keluaran, metode pelaksanaan, waktu, dan rincian alokasi biaya sebuah kegiatan.'],
    ['GAP (Gender Analysis Pathway)', 'Metode analisis gender resmi yang diwajibkan Bappenas dan Kementerian PPPA untuk memastikan kebijakan dan anggaran pemerintah responsif terhadap isu kesetaraan gender.'],
    ['DIPA (Daftar Isian Pelaksanaan Anggaran)', 'Dokumen pelaksanaan anggaran yang diterbitkan oleh Kementerian Keuangan dan menjadi dasar bagi kementerian/lembaga untuk melakukan pengeluaran uang negara.'],
    ['RO / KRO', 'Rincian Output (RO) dan Klasifikasi Rincian Output (KRO), yaitu kodefikasi hierarki penganggaran berbasis kinerja dalam sistem penganggaran nasional.'],
    ['PHPWord', 'Pustaka open-source berbasis PHP untuk membaca, membuat, dan memanipulasi file dokumen pemrosesan kata berbasis format OpenXML (.docx).'],
    ['TemplateProcessor', 'Kelas khusus di dalam PHPWord yang bekerja dengan membedah file XML dokumen Word dan melakukan substitusi tag penanda (${tag_name}) secara efisien.'],
    ['Merge-Field Tag', 'Penanda unik berupa variabel (contoh: ${field_001}) yang disisipkan ke dalam dokumen template Word untuk digantikan oleh data masukan dinamis.'],
    ['LibreOffice Headless', 'Mode eksekusi aplikasi perkantoran LibreOffice melalui antarmuka baris perintah (CLI) tanpa memerlukan Graphical User Interface (GUI), digunakan untuk konversi dokumen ke PDF di server.'],
    ['SPBE', 'Sistem Pemerintahan Berbasis Elektronik, yaitu penyelenggaraan pemerintahan yang memanfaatkan teknologi informasi dan komunikasi untuk memberikan layanan kepada instansi dan masyarakat.'],
    ['Cold Start', 'Waktu tunda yang dialami sebuah server/kontainer cloud pada layanan gratis ketika menerima permintaan pertama setelah berada dalam kondisi hibernasi (sleep).'],
    ['MoSCoW Method', 'Teknik penentuan prioritas kebutuhan perangkat lunak yang membagi fitur ke dalam kategori Must Have (Wajib), Should Have (Harus Ada), Could Have (Bisa Ada), dan Won\'t Have (Ditunda).']
];

foreach ($glosData as $i => $row) {
    $bg = ($i % 2 === 1) ? ['bgColor' => $colorBgAltRow] : [];
    $glosTable->addRow();
    $glosTable->addCell(2500, array_merge($bg, ['bgColor' => 'F1F5F9']))->addText($row[0], $fontTblCellB, ['spaceAfter' => 0]);
    $glosTable->addCell(7000, $bg)->addText($row[1], $fontTblCell, ['spaceAfter' => 0]);
}

$section->addTextBreak(2);

// Lembar Tanda Tangan Persetujuan PRD
$signTable = $section->addTable(['alignment' => JcTable::CENTER, 'width' => 100 * 50, 'unit' => 'pct']);
$signTable->addRow();
$cell1 = $signTable->addCell(4750);
$cell1->addText('Disusun Oleh:', ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'color' => $colorNavyDark]);
$cell1->addTextBreak(3);
$cell1->addText('( Tim Business Analyst & Software Engineer )', ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'underline' => 'single']);
$cell1->addText('Biro Perencanaan dan Kerjasama / Pengembang Sistem', ['name' => 'Calibri', 'size' => 9, 'color' => $colorSlateMuted]);

$cell2 = $signTable->addCell(4750);
$cell2->addText('Disetujui Oleh:', ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'color' => $colorNavyDark]);
$cell2->addTextBreak(3);
$cell2->addText('( Penanggung Jawab Proyek / Product Owner )', ['name' => 'Calibri', 'size' => 10, 'bold' => true, 'underline' => 'single']);
$cell2->addText('Koordinator Tata Kelola SPBE & Perencanaan', ['name' => 'Calibri', 'size' => 9, 'color' => $colorSlateMuted]);

// -------------------------------------------------------------
// EKSPOR KE FILE DOKUMEN WORD (.DOCX)
// -------------------------------------------------------------
$targetPath = __DIR__ . '/PRD_Sistem_Digitalisasi_KAK.docx';
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($targetPath);

echo "SUCCESS: File PRD berhasil dibuat di " . $targetPath . " (Ukuran: " . filesize($targetPath) . " bytes)\n";
