# Sistem Digitalisasi Template Dokumen KAK (Kerangka Acuan Kegiatan)

Aplikasi berbasis Laravel untuk mengisi template dokumen Kerangka Acuan Kegiatan (KAK) Kemenko PMK secara digital. Sistem ini membaca placeholder template master Word (`.docx`), menyediakan formulir interaktif multi-step wizard, dan men-generate dokumen resmi dengan layout dan format yang persis sama dengan dokumen master aslinya.

Pengguna dapat memilih untuk mengunduh output dokumen dalam format **Word (.docx)** atau **PDF (.pdf)**.

---

## Fitur Utama

1. **Artisan Template Merge-Field Engine (`php artisan kak:prepare-template`)**:
   - Membaca `word/document.xml` dari template master Word.
   - Mengubah setiap placeholder run (`……`, tanda highlight kuning, teks instruksi seperti narasi, dasar hukum, anggaran) menjadi merge tag unik `${field_001}`, `${field_002}`, dst.
   - Menghasilkan metadata pemetaan di `resources/templates/field_map.json` dan template siap pakai di `resources/templates/kak_template_merged.docx`.

2. **Formulir Multi-Step Wizard Interaktif**:
   - Dikelompokkan otomatis berdasarkan seksi dokumen (Identitas KAK, Latar Belakang & Dasar Hukum, Penerima Manfaat, Strategi Pencapaian Keluaran, Kurun Waktu Pelaksanaan, Biaya, Tanda Tangan).
   - Menggunakan Alpine.js untuk navigasi antar-step yang responsif dan mulus tanpa reload halaman.
   - Penyimpanan draf otomatis ke sesi per step sehingga progres pengisian aman.
   - Deteksi tipe input otomatis (`<textarea>` untuk narasi/uraian, `<input type="text">` untuk data singkat).

3. **Pilihan Format Output Fleksibel (Word / PDF)**:
   - **Word (.docx)**: Dibuat langsung secara native menggunakan `PhpOffice/PhpWord` TemplateProcessor. Sangat cepat, ringan, dan dokumen dapat diedit kembali.
   - **PDF (.pdf)**: Dikonversi menggunakan LibreOffice headless engine (`soffice --headless --convert-to pdf`). File DOCX sementara otomatis dibersihkan.
   - Konversi PDF **hanya dijalankan** jika pengguna secara spesifik memilih format PDF.

4. **Regenerasi Dokumen Instan**:
   - Dokumen yang telah disimpan dapat di-generate ulang ke format lain (misal dari DOCX ke PDF atau sebaliknya) dalam sekali klik tanpa mengisi ulang data.

5. **Single Adaptive Download Route**:
   - Satu route unduh (`/submissions/{id}/download`) yang otomatis mendeteksi format dan mengirimkan file dengan MIME type resmi yang tepat (`application/pdf` atau `application/vnd.openxmlformats-officedocument.wordprocessingml.document`).

---

## Persyaratan Sistem

- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `zip`, `xml`, `gd`)
- Composer >= 2.x
- MySQL Database (misalnya via Laragon / XAMPP)
- **LibreOffice (soffice)** *(Hanya dibutuhkan apabila pengguna memilih unduh format PDF)*:
  - **Linux (Ubuntu/Debian)**: `sudo apt update && sudo apt install libreoffice -y`
  - **Linux (Alpine/Docker)**: `apk add libreoffice`
  - **Windows**: Unduh installer resmi dari [libreoffice.org](https://www.libreoffice.org/download/download-libreoffice/) dan tambahkan path `soffice.exe` ke Environment PATH atau konfigurasi `.env`.
  - *Catatan*: Jika LibreOffice belum terpasang di sistem lokal Anda, pembuatan dokumen dalam format **Word (.docx)** tetap berjalan 100% secara native tanpa kendala.

---

## Panduan Instalasi & Menjalankan Aplikasi

### 1. Clone atau Buka Folder Project
```bash
cd "d:/MagangHub/Day 2/Sistem Input"
```

### 2. Install Dependensi PHP
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file `.env.example` jika belum ada `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Pastikan konfigurasi database di `.env` sudah sesuai (contoh MySQL lokal Laragon):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kak_digital
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SOFFICE_BINARY=soffice
```

### 4. Jalankan Migrasi Database
```bash
php artisan migrate
```

### 5. Jalankan Template Prepare Command (Wajib Pertama Kali)
Jalankan perintah ini untuk memproses template master menjadi merge-field template:
```bash
php artisan kak:prepare-template
```
Perintah ini akan membaca `template/Copy of Format Digitalisasi KAK.docx`, memetakan 160 field placeholder, dan membuat:
- `resources/templates/field_map.json`
- `resources/templates/kak_template_merged.docx`

### 6. Jalankan Server Pengembangan
```bash
php artisan serve
```
Akses aplikasi melalui browser di: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## Struktur Direktori Utama

```
├── app/
│   ├── Console/Commands/
│   │   └── PrepareKakTemplate.php      # Command artisan untuk parsing & merge tag template
│   ├── Http/Controllers/
│   │   └── KakSubmissionController.php # Controller wizard, finalize, show, download, regenerate
│   ├── Models/
│   │   └── KakSubmission.php           # Model Eloquent data KAK
│   └── Services/
│       └── KakDocumentGenerator.php    # Service generate DOCX & konversi PDF via LibreOffice
├── database/migrations/
│   └── *_create_kak_submissions_table.php
├── resources/
│   ├── templates/
│   │   ├── field_map.json              # Metadata pemetaan merge tag
│   │   └── kak_template_merged.docx    # Template docx hasil penanaman tag ${field_xxx}
│   └── views/
│       ├── layouts/app.blade.php       # Layout Tailwind CSS + Alpine.js
│       └── submissions/
│           ├── index.blade.php         # Riwayat submission
│           ├── wizard.blade.php        # Multi-step wizard form
│           └── show.blade.php          # Ringkasan hasil & tombol download
├── storage/app/
│   ├── output/                         # Lokasi file hasil generate (.docx & .pdf)
│   └── templates/                      # Salinan template
├── template/
│   └── Copy of Format Digitalisasi KAK.docx # Template master dokumen asli
├── Dockerfile                          # Docker deployment untuk Render / Cloud
└── README.md
```

---

## Fitur AI: Asisten Kontekstual & Query Bahasa Alami

Aplikasi ini telah diperkaya dengan dua fitur kecerdasan buatan (AI) yang mengadopsi prinsip arsitektur dari proyek open source **`alibaba/open-code-review`**:

### 1. AI Asisten Kontekstual (Chat Widget di Wizard)
- **Tujuan**: Membantu pengguna (staf instansi/perencana) memahami istilah teknis perencanaan (seperti GAP, DIPA, RO/KRO, SBM, RAB) dan panduan pengisian formulir KAK secara langsung pada tiap tahapan wizard.
- **Letak**: Tombol floating widget di pojok kanan bawah halaman wizard pengisian KAK (`/submissions/create`).
- **Cara Kerja**:
  1. Saat pengguna bertanya, frontend Alpine.js menyertakan informasi step yang sedang aktif (`current_section`).
  2. `KnowledgeRetriever` melakukan pencarian *keyword matching* deterministik terhadap metadata `resources/templates/field_map.json` dan glosarium resmi `resources/data/glossary.json`.
  3. Konteks spesifik disisipkan ke *scenario-tuned system prompt*, kemudian dikirim ke API AI (`swift`).
  4. Riwayat tanya-jawab dicatat ke database (`ai_assistant_logs`) untuk audit dan evaluasi kualitas jawaban.

### 2. AI Query Riwayat Submission (Tanya Data dalam Bahasa Alami)
- **Tujuan**: Memungkinkan pencarian arsip dokumen KAK menggunakan bahasa percakapan sehari-hari tanpa harus mengatur filter manual satu per satu.
- **Contoh Pertanyaan**:
  - `"KAK draft bulan ini"`
  - `"Dokumen final tahun 2026"`
  - `"KAK dengan anggaran di atas 500 juta"`
  - `"Dokumen KAK bulan lalu"`
- **Cara Kerja**:
  1. Pengguna memasukkan pertanyaan ke dalam search bar AI pada halaman `/submissions`.
  2. `KakQueryAssistantService::parseQuery()` meminta model AI menerjemahkan maksud pengguna menjadi JSON filter terstruktur.
  3. Hasil JSON divalidasi dan disanitasi ketat oleh `KakQueryFilterSchema` (hanya 5 field yang diizinkan: `status`, `bulan`, `tahun`, `min_anggaran`, `max_anggaran`).
  4. Query builder Eloquent Laravel deterministik mengeksekusi pencarian ke database berdasarkan parameter yang tervalidasi.
  5. Antarmuka menampilkan badge chip filter yang dipahami AI sebagai konfirmasi visual kepada pengguna, serta merender daftar dokumen yang sesuai secara instan via AJAX.

---

## Konfigurasi Klien AI (`.env`)

Sistem menggunakan API kompatibel OpenAI (Swift AI) via package `openai-php/client`:

```env
SWIFT_AI_BASE_URI=https://ukisai.com/api/swift/v1
SWIFT_AI_API_KEY=none
```

Konfigurasi ini dimuat di `config/services.php`:
```php
'swift_ai' => [
    'base_uri' => env('SWIFT_AI_BASE_URI', 'https://ukisai.com/api/swift/v1'),
    'api_key' => env('SWIFT_AI_API_KEY', 'none'),
    'model' => 'swift',
],
```

---

## Prinsip Arsitektur & Guardrail Keamanan (WAJIB)

Mengikuti pola arsitektur *hybrid deterministic + agent*:

1. **AI TIDAK PERNAH Menyentuh Database Secara Langsung**:
   - AI tidak diberi akses ke query builder, raw SQL, skema tabel, atau koneksi DB.
   - AI hanya bertindak sebagai parser bahasa alami (ekstraksi intent parameter). Eksekusi query database 100% ditangani oleh Eloquent builder Laravel yang aman dari SQL Injection.
2. **Output Terstruktur & Whitelist Schema (`KakQueryFilterSchema`)**:
   - Parameter hasil ekstraksi AI divalidasi ketat terhadap skema yang diizinkan:
     - `status`: hanya menerima `'draft'` atau `'final'`.
     - `bulan`: integer `1-12` atau `'current'`.
     - `tahun`: integer 4 digit (`2000-2100`).
     - `min_anggaran` & `max_anggaran`: numerik non-negatif.
   - Setiap field di luar whitelist diabaikan/dibersihkan secara otomatis.
3. **Rate Limiting Per User/Session**:
   - Kedua endpoint AI (`POST /kak/assistant/ask` dan `POST /kak/history/ai-search`) dilindungi middleware `throttle:20,1` (maksimal 20 request per menit per user/IP) untuk mencegah penyalahgunaan kuota API.
4. **Timeout & Graceful Fallback**:
   - Permintaan HTTP ke API AI dibatasi timeout maksimal 10 detik dengan koneksi timeout 5 detik.
   - Jika API AI lambat atau tidak dapat diakses, sistem menampilkan pesan alternatif yang ramah tanpa pernah mengganggu ataupun memblokir proses pengisian wizard dan pengelolaan dokumen.
5. **Caching Konteks Pengetahuan**:
   - Hasil pencarian konteks `KnowledgeRetriever::retrieveContext()` disimpan dalam in-memory cache berdasarkan hash query dan seksi untuk menghindari proses pencarian berulang yang identik.

---

## Pengujian Fitur AI

Untuk menguji fitur AI secara otomatis:
```bash
php artisan test --filter=AiFeaturesTest
```

Pengujian manual:
1. **Asisten Wizard**: Buka `/submissions/create`, klik tombol **Tanya Asisten KAK** di pojok kanan bawah, ketik pertanyaan `"apa itu GAP"` atau klik chip saran.
2. **Pencarian Riwayat**: Buka `/submissions`, masukkan `"KAK draft bulan ini"` pada search bar AI, lalu tekan tombol **Cari dengan AI**. Filter chip `Status: Draft` dan `Bulan Ini` akan muncul beserta daftar dokumen yang cocok.
