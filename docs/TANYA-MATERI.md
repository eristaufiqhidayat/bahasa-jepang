# Tanya Materi tanpa AI

Menu **Tanya Materi** di halaman belajar Laravel mencari jawaban terkurasi dalam database. Tidak memerlukan API key, layanan AI, server tambahan, worker, symlink, `exec`, atau `shell_exec`. Hosting PHP/Laravel dan database yang sama digunakan.

## Aktivasi hosting yang sudah terpasang

Jalankan dari direktori Laravel di hosting, setelah membuat backup database seperti saat upgrade sebelumnya:

```bash
git pull origin main
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=JlptCurriculumSeeder --force
php artisan db:seed --class=MaterialChatSeeder --force
php artisan optimize
```

Tidak ada perubahan dependensi Composer. Document root tetap `public/`; `APP_URL=https://japantest.id`, session dan storage Laravel harus dapat ditulis. Untuk memastikan CSS/JS terbaru, muat ulang halaman dengan hard refresh. Jika memakai CDN, bersihkan cache halaman dan aset terkait.

Seeder menggunakan `firstOrCreate`: bisa diulang, tidak menggandakan jawaban atau menimpa koreksi/status admin. Pelajaran lama tidak otomatis diterbitkan. Kosakata hanya dibuatkan jawaban jika pelajaran induknya published; sesudah menerbitkan kosakata lama, jalankan `MaterialChatSeeder` lagi. Instalasi baru: `php artisan migrate --seed` sudah menjalankan kedua seeder.

## Isi bank awal

- 12 jawaban fondasi: mulai belajar, hiragana, katakana, kanji, romaji, salam, perkenalan, angka, vokal panjang, っ kecil, yōon, dan cakupan chat.
- 30 jawaban Bab 1–10: konsep utama, contoh kalimat dan cara belajar per bab.
- 15 jawaban dasar N4 Bab 26–30: んです, potensial, ながら, keadaan hasil, てある/ておく, contoh dan cara belajar.
- Jawaban kosakata dari data yang published: maksimal 27 untuk paket awal saat semua empat pelajaran pemula sudah diterbitkan. Total paket awal saat itu 84 jawaban.

Sumber konsep dicantumkan sebagai bab rangkuman Japantest. Ini penjelasan/contoh editorial, bukan salinan soal buku atau soal resmi JLPT. Pemetaan N5/N4 bukan kesetaraan resmi bab dengan level. Bank awal **belum mencakup semua materi N5/N4**. Jawaban awal bertanda perlu tinjauan pengajar; pengajar dapat mengubah menjadi `reviewed` sesudah memeriksa.

## Perilaku pencarian dan penggunaan

1. Pilih Fondasi, N5 atau N4; buka dari tombol **Tanyakan materi ini** untuk membatasi sumber pada satu pelajaran. Materi umum fondasi tetap bisa dicari.
2. Masukkan satu topik. Kata kunci/sinonim dan pertanyaan utama digunakan untuk menentukan kecocokan; tidak ada pemahaman bebas atau jawaban generatif. Kata kunci dipisah dengan koma pada admin.
3. Jawaban cocok menyertakan label sumber dan tombol membuka materi. Jawaban dengan sumber pelajaran draft tidak terlihat publik.
4. Pertanyaan yang ambigu/tidak ditemukan mendapat saran atau materi terkait serta masuk antrean pengajar. Tidak mengambil kunci atau pembahasan dari tabel soal maupun virtual test.
5. Kuota **20 pertanyaan per hari per sesi browser**, termasuk yang tidak ditemukan; reset pukul 00.00 WIB (`Asia/Jakarta`). Retry dengan request UUID yang sama tidak memotong kuota dua kali. Ada throttle 30 permintaan/menit per IP, termasuk riwayat dan laporan.
6. Sepuluh percakapan terakhir dimuat kembali dari server pada sesi yang sama. Riwayat dan laporan tidak dapat diakses oleh sesi lain. Isi jawaban adalah snapshot saat ditanyakan; koreksi admin berlaku untuk pertanyaan berikutnya.
7. Tombol **Laporkan jawaban keliru** menyimpan satu laporan per pesan; pengajar meninjau pertanyaan, jawaban dan alasan.

Sesi baru/penghapusan cookie dapat memperoleh kuota baru. Untuk kuota yang terikat identitas dan sinkronisasi perangkat, perlu autentikasi siswa pada tahap selanjutnya. Chat hanya halaman web Laravel; aplikasi Flutter belum memakai endpoint session/CSRF ini.

## Pengelolaan

Masuk `/login` → **Tanya Materi & laporan** (`/admin/material-answers`). Admin bisa tambah/edit/hapus pertanyaan utama, jawaban teks biasa, sinonim, tingkat, sumber pelajaran, label sumber, status dan tinjauan. Pertanyaan belum terjawab dapat dijadikan FAQ. Untuk laporan, edit bank jawaban lalu tulis catatan tindak lanjut dan tandai selesai.

Pertanyaan dan laporan disimpan dalam database untuk ditinjau pengajar. Antarmuka mengingatkan pengguna agar tidak mengirim data pribadi. Tidak ada penghapusan otomatis atau akun siswa; tentukan kebijakan retensi sebelum memperluas penggunaan. Data chat tidak dikirim ke penyedia AI.

## Pemeriksaan setelah upgrade

- Buka Tanya Materi, pilih N5, ketik **Apa fungsi partikel wa?**. Jawaban menjelaskan topik, cara baca wa dan contoh dengan sumber Bab 1.
- Pilih N4, ketik **Apa fungsi んです?**. Jawaban sumber Bab 26 tampil.
- Coba pertanyaan di luar bank: jawaban tidak dikarang dan masuk antrean pengajar.
- Kirim laporan, lalu periksa antrean di admin.
- Ubah FAQ menjadi draft; pastikan pertanyaan berikutnya tidak mengembalikan jawaban tersebut.

Pengujian pengembangan: `vendor/bin/phpunit` (tidak membutuhkan `proc_open` dari runner Artisan), `php artisan view:cache`, serta uji DOM/HTTP pada layar pengguna.
