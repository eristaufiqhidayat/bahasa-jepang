# Haru — Flutter Belajar Jepang

Aplikasi Flutter native Android/iOS dan web mengikuti mockup Haru: hijau, kartu materi, enam menu, sidebar desktop, navigasi bawah HP. Tagline: **Belajar Minna no Nihongo**. Materi berasal dari Laravel API `https://japantest.id/api/v1`.

## Jalankan di Mac

Dari folder repository:

```bash
git pull origin main
cd flutter
flutter pub get
flutter devices
flutter run
```

Pilih emulator Android atau simulator iPhone dari daftar perangkat. Proyek memerlukan Flutter 3.38.4+ dengan Dart 3.10+, Android SDK dan Xcode untuk target masing-masing. Tidak memerlukan login siswa; progres tersimpan di perangkat. Platform native Android/iOS/web disertakan.

## Flutter web

```bash
flutter run -d chrome --web-port=3000
```

Pada `.env` Laravel hosting, izinkan origin web:

```dotenv
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

Untuk domain Flutter web produksi, tambahkan origin tepatnya dipisahkan koma. Jalankan `php artisan optimize:clear` dan `php artisan optimize` setelah perubahan `.env`. Origin harus sama protokol, host, dan port; misalnya `http://127.0.0.1:3000` berbeda dari `http://localhost:3000`. Pull Laravel terbaru di hosting juga menambahkan CORS pada audio belajar. Android/iOS tidak memerlukan CORS.

## API lain

```bash
flutter run --dart-define=API_BASE_URL=https://japantest.id
```

Alamat harus root Laravel, tanpa `/api/v1`. Semua pagination API dibaca. Pelajaran published saja ditampilkan. Materi belum published akan menghasilkan tampilan kosong, bukan materi contoh hardcoded.

## Fitur

- Beranda: lanjutkan pelajaran, ringkasan progres, target menit, kata pilihan.
- Belajar: daftar pelajaran, isi, kosakata, contoh Jepang, arti, romaji, tandai selesai.
- Huruf: hiragana/katakana dari database, cara baca dan tombol audio.
- Kosakata: cari Jepang/romaji/arti, kategori, contoh, tandai dikuasai.
- Latihan: kuis campuran/per pelajaran; tiga/empat pilihan, jawaban dan pembahasan diperiksa server.
- Profil: riwayat kuis, progres, romaji opsional, target harian, reset dengan konfirmasi.
- Audio: rekaman dari endpoint Laravel yang tidak memerlukan storage symlink. Jika belum ada rekaman, TTS Jepang perangkat; suara Jepang harus tersedia. Soal mendengarkan menggunakan rekaman.
- Koneksi: indikator loading, pesan kegagalan, retry, refresh materi.

Progres lokal Flutter berbeda dari progres browser Laravel. Belum ada akun siswa, sinkronisasi server, durasi/streak otomatis, atau unduhan materi offline. Nama/tagline mengikuti pilihan proyek; materi contoh masih materi pemula yang disiapkan sendiri, belum mencakup seluruh buku Minna no Nihongo.

## Pengujian dan build

```bash
flutter analyze
flutter test
flutter build web --dart-define=API_BASE_URL=https://japantest.id
flutter build apk --release --dart-define=API_BASE_URL=https://japantest.id
```

Untuk distribusi Google Play, gunakan keystore rilis milik Bapak dan konfigurasi signing Android, lalu:

```bash
flutter build appbundle --release --dart-define=API_BASE_URL=https://japantest.id
```

Signing bawaan pengembangan harus diganti sebelum publikasi. ID Android awal `id.japantest.haru_belajar_jepang`; iOS `id.japantest.haruBelajarJepang`. Nama tampilan Haru. Target iOS minimal 15; Android memiliki izin INTERNET dan queries TTS. Audio/layar perlu diuji juga pada perangkat nyata sebelum rilis.

Konfigurasi Android mengikuti template resmi Flutter 3.38.4: AGP 8.11.1, Gradle 8.14, Kotlin 2.2.20, Java 17. Android SDK 36 diperlukan ketika memakai Flutter 3.38.4.

Validasi lingkungan pengembangan: Flutter 3.47.6 / Dart 3.13.5, analisis bersih, 8 tes lulus, build web berhasil. Disarankan memakai Flutter stable terbaru (`flutter upgrade`). Android/iOS belum dibuild di lingkungan ini; memerlukan SDK/Xcode.
