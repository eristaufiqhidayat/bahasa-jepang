# Belajar Bahasa Jepang — Laravel

Backend Laravel 12 + panel admin Blade berbahasa Indonesia untuk aplikasi Flutter pemula. PHP 8.2+, Composer 2, MySQL 8 (`utf8mb4`) atau SQLite. Panel tidak membutuhkan npm atau build frontend; CSS lokal.

## Fitur

- Login khusus admin, pembatasan percobaan masuk, CSRF, logout.
- CRUD pelajaran (draft/published), kosakata, hiragana/katakana, dan soal pilihan ganda dengan pembahasan.
- Unggah/ganti/hapus audio MP3/WAV/OGG/M4A maksimal 10 MB; audio wajib untuk soal mendengarkan.
- Pencarian, pagination, urutan materi, tampilan responsif.
- API v1 hanya mengekspos pelajaran published dan menyembunyikan kunci soal sebelum menjawab.
- Seeder empat pelajaran draft (salam, perkenalan, angka, minuman), 22 kosakata, 8 soal, 92 huruf dasar. Seeder tidak menimpa pelajaran yang sudah ada.

## Instalasi lokal (SQLite)

```bash
git clone https://github.com/eristaufiqhidayat/bahasa-jepang.git
cd bahasa-jepang
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan app:create-admin admin@example.com --name="Eris Taufiq"
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8023
```

Masukkan kata sandi minimal 12 karakter ketika diminta. Tidak ada akun/kata sandi bawaan. Buka `http://localhost:8023/login`. Untuk MySQL, ganti konfigurasi berikut sebelum menjalankan migrate:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bahasa_jepang
DB_USERNAME=bahasa_jepang
DB_PASSWORD=isi_password_database
```

Buat database `bahasa_jepang` dengan charset `utf8mb4` dan collation `utf8mb4_unicode_ci`. Dalam Docker, `DB_HOST` adalah nama service MySQL (misalnya `mysql8`). PHP memerlukan `pdo_mysql`, `mbstring`, `fileinfo`, `xml`, `curl`, dan `zip`. Pastikan `storage` serta `bootstrap/cache` bisa ditulis pengguna PHP-FPM.

## Menambahkan materi

1. Masuk panel → Pelajaran → Tambah. Slug contoh: `salam-pagi`, urutan 1, status draft.
2. Isi penjelasan Indonesia, contoh Jepang, romaji, arti, durasi, dan audio jika tersedia. Isi materi berupa teks biasa, bukan HTML.
3. Tambahkan kosakata dan soal lalu pilih pelajaran induknya. Soal memiliki empat pilihan dan satu kunci jawaban.
4. Pilih jenis mendengarkan hanya setelah audio tersedia. Tambahkan pembahasan.
5. Periksa arti, bunyi, dan konteks bersama pengajar bahasa Jepang. Publikasikan dengan status published.
6. Edit/hapus melalui daftar. Menghapus pelajaran juga menghapus kosakata, soal, dan audio terkait.

Huruf dasar mencakup 46 hiragana + 46 katakana modern. Dakuten, handakuten, dan kombinasi yōon dapat ditambahkan melalui CRUD. `を` dicatat sebagai `wo` pada tabel kana; sebagai partikel umumnya diucapkan `o`.

Audio asli belum disertakan. Seluruh data contoh berupa draft untuk diperiksa sebelum publikasi. Pastikan izin penggunaan materi/audio.

## API untuk Flutter

| Metode | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/v1/lessons` | Pelajaran published, pagination |
| GET | `/api/v1/lessons/{id}` | Materi, kosakata, soal tanpa kunci/pembahasan |
| GET | `/api/v1/characters?script=hiragana` | Huruf (filter opsional hiragana/katakana) |
| GET | `/api/v1/vocabularies` | Kosakata dari pelajaran published |
| POST | `/api/v1/questions/{id}/answer` | Periksa `{"answer_index": 0}` (indeks A=0 … D=3) |

Contoh respons jawaban: `{"correct":true,"correct_index":0,"explanation":"..."}`. API publik untuk belajar dan dibatasi 60 permintaan/menit per IP; belum menyimpan progres/skor, menyediakan akun siswa, atau autentikasi token Flutter. Fungsi tersebut tahap berikutnya. URL audio: gabungkan `audio_path` dengan `${APP_URL}/storage/`. Atur `APP_URL` sesuai alamat server dan jalankan `storage:link`. CORS Flutter web diatur lewat `CORS_ALLOWED_ORIGINS`, misalnya `http://localhost:3000,https://belajar.example.com`; jalankan Flutter dengan port yang sesuai.

## Hosting / Docker PHP-FPM

- Document root harus menunjuk direktori `public/`, bukan root repository.
- Jangan commit `.env`. Gunakan `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS.
- Jalankan `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan storage:link`, `php artisan optimize`.
- Jalankan seeder hanya jika membutuhkan materi contoh: `php artisan db:seed --force`.
- Buat admin melalui command yang sama pada terminal server.
- Berikan izin tulis `storage` dan `bootstrap/cache` kepada pengguna PHP-FPM; jangan memakai chmod 777.
- Unggahan memerlukan `upload_max_filesize=12M`, `post_max_size=16M`, dan untuk nginx `client_max_body_size 16m`.
- Jika hosting tidak mendukung symlink, gunakan pemetaan storage yang disediakan hosting sebelum mengaktifkan audio publik.

## Pengujian

```bash
php artisan test
php artisan view:cache
```

Tes menggunakan SQLite in-memory; mencakup otorisasi admin, CRUD seluruh jenis, validasi audio, cascade delete, draft/public API, kunci jawaban, dan seeder berulang. Workflow GitHub Actions disertakan.
