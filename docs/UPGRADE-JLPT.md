# Pembaruan Haru / Japantest — kurikulum dan virtual test

## Memperbarui japantest.id

Jalankan dari direktori Laravel di hosting setelah backup database melalui cPanel/phpMyAdmin:

```bash
git pull origin main
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=JlptCurriculumSeeder --force
php artisan optimize
```

Tidak ada dependensi Composer atau build npm baru. Perintah di atas tidak memakai Tinker, `shell_exec()`, atau `storage:link`. Audio tetap dilayani controller Laravel tanpa symlink. Gunakan binary PHP CLI yang biasa dipakai pada hosting jika `php` default berbeda versinya.

Seeder menambah data melalui `reference_id` atau slug unik, tidak menimpa materi/pembahasan/status/audio yang sudah diedit admin. Jalankan ulang bila perlu; tidak perlu `migrate:fresh`, menghapus tabel, atau membuat ulang akun admin. Empat pelajaran lama mengikuti status yang sudah ada; pada database baru, publikasikan pelajaran pemula lewat panel agar kosakata/soalnya terlihat publik. Lima puluh rangkuman baru diterbitkan sebagai **materi awal, perlu tinjauan pengajar**.

Buka beranda setelah pembaruan. Menu Huruf dan Kosakata tetap tersedia. Pilih N5 atau N4 pada **Jalur belajar**, lalu buka **Virtual Test** untuk mini test. CSS/JS diberi versi berdasarkan waktu berkas agar perubahan tampilan segera dimuat.

## Data yang dimasukkan

| Data | Paket bawaan |
| --- | --- |
| Huruf | 46 hiragana + 46 katakana lama |
| Kosakata | 27 kata lama, kategori/pencarian/audio/penanda dikenal tetap ada |
| Pelajaran lama | Salam, perkenalan, angka, minuman |
| Rangkuman tambahan | Bab 1–50; 20 dipetakan ke N5 dan 30 ke N4 |
| Soal tambahan | 77 soal orisinal: 54 tata bahasa, 9 kosakata, 6 membaca, 8 rancangan menyimak |
| Mini virtual test | N5 dan N4; masing-masing 10 soal, 8 menit |
| Rancangan simulasi penuh | N5, N4, N3, N2, N1; struktur waktu dan acuan penilaian |

Total instalasi bawaan: 54 pelajaran, 90 soal, 27 kosakata, 92 huruf, 7 jalur, 7 template. Hitungan publik mengikuti status materi. Delapan soal menyimak disimpan dengan naskah untuk pengajar dan tidak ditampilkan sebagai latihan yang dapat dimainkan sebelum ada rekaman. Naskah, kunci, dan pembahasan tidak disertakan dalam data halaman/API sebelum menjawab.

Sumber terstruktur berada di `database/data/japantest-minna-reference.json`. Rangkuman dan soal menggunakan tulisan orisinal Japantest berdasarkan konsep Bab 1–50, bukan salinan buku/soal resmi. Pemetaan bersifat editorial sementara; bukan kesetaraan resmi bab Minna dengan level JLPT. Jalur N3–N1 dan Profesional masih rancangan tanpa klaim kurikulum lengkap. Selesaikan tinjauan guru Jepang lewat kolom **Tinjauan pengajar** pada materi/soal.

## Panel admin

- Pelajaran: tambah/edit level, bab, pola kalimat, isi, status publikasi, dan tinjauan pengajar.
- Soal: tambah/edit level, keterampilan, bagian tes, jenis item, pembahasan, naskah audio, rekaman, dan tinjauan pengajar. Pilihan tiga atau empat jawaban tetap didukung.
- Virtual test: tambah/edit/hapus paket mini; pilih tingkat, durasi, status, dan ID soal. Paket published harus memakai materi published, tingkat yang cocok, dan rekaman jika memilih soal menyimak.
- Rancangan simulasi penuh ditampilkan sebagai acuan baca, belum dapat dimainkan atau diterbitkan lewat pengelola mini test.

## Cara kerja mini virtual test

Timer dimulai di server dan tetap berjalan setelah tab ditutup. Jawaban disimpan di server setiap pilihan, tanpa menampilkan kunci sebelum selesai. Browser menyimpan ID tes aktif untuk pemulihan reload; akses memerlukan sesi browser yang sama. Browser/incognito lain tidak dapat mengambil hasil hanya dengan menyalin URL/ID. Sesi Laravel yang sudah hilang tidak dapat memulihkan tes anonim.

Soal dan kunci disalin sebagai snapshot saat mulai agar perubahan admin tidak mengubah penilaian tes berjalan. Pengiriman selesai dapat diulang dengan hasil tetap sama. Sesudah batas waktu, server mengunci tes dan menilai jawaban yang telah tersimpan; jawaban terlambat diabaikan. Hasil: jumlah benar, akurasi per keterampilan, rekomendasi, dan pembahasan. Tidak dikonversi menjadi skor resmi 0–180, keputusan lulus JLPT, atau sertifikat resmi.

Progres lama memakai kunci browser `haru-learning-v1`; ID pelajaran/kosakata lama tidak diubah. Preferensi romaji, target menit, materi selesai, dan riwayat kuis tetap dibaca. Progres belajar lokal belum disinkronkan ke akun/perangkat lain. Hasil mini test server dapat dibuka dari riwayat pada sesi yang sama. Target harian belum menghitung durasi otomatis.

## API dan pengujian

`GET /api/v1/tracks` memberikan jalur belajar. `GET /api/v1/lessons?level=N5` (atau foundation/N4/N3/N2/N1/pro) menambahkan filter; respons paginasi dan endpoint lama tetap kompatibel untuk Flutter. Endpoint mini test web menggunakan sesi dan CSRF di `/belajar/virtual-tests`.

```bash
php artisan test
php artisan view:cache
```

Tes mencakup seeder ulang/preservasi edit, pemetaan dan data publik, otorisasi admin, kepemilikan tes, jawaban tidak sah, timer server, pemulihan, snapshot soal, hasil idempoten, serta paket belum siap. Alur DOM halaman juga diuji terhadap server Laravel dengan progres lama, huruf/kosakata, pemilihan jalur, penilaian latihan, save/reload mini test, dan pembahasan hasil.

Acuan struktur: https://www.jlpt.jp/sp/e/guideline/testsections.html

Acuan penilaian: https://www.jlpt.jp/sp/e/guideline/results.html
