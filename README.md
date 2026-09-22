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

## Deployment Menggunakan Docker (Render / Cloud)

Project ini telah dilengkapi dengan `Dockerfile` siap pakai yang mencakup PHP 8.3, Composer, dan LibreOffice:
1. Push repository ke Git (GitHub / GitLab).
2. Di dashboard **Render.com**, buat **Web Service** baru dari repository ini.
3. Pilih environment **Docker**.
4. Set variabel environment database (MySQL).
5. Render akan secara otomatis membangun container lengkap dengan LibreOffice headless sehingga fitur generate DOCX maupun konversi PDF langsung aktif.
