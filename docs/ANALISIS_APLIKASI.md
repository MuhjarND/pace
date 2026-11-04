# Analisis awal PTSP Online

Tanggal: 27 Oktober 2026, WIT. Lokasi proyek: `C:\xampp\htdocs\ptsp_online`.

## Pembaruan UI 31 Oktober 2026

Shell, dashboard, katalog, beranda dan login diperbaiki mengikuti Frontend Design Premium. Palet terbaru dari referensi pengguna memakai putih #FFFFFF, oranye #EF5F18, dan Scarlet Bikini #261A66. Penyesuaian Build Web Apps mempertahankan tata letak dan isi; teks tombol memakai ungu turunan agar kontras cukup. Katalog memiliki filter kategori dan pencarian yang tersimpan di URL, kontrol bersama mendukung validasi inline dan modal yang dapat diakses dengan keyboard, serta drawer seluler mengelola fokus. Tidak ada perubahan database atau aturan antrean. Kontrak ada di `DESIGN.md` dan `UX-CONTRACT.md`; hasil pemeriksaan UI sebelumnya dan batas buktinya ada di [UI_REVIEW.md](UI_REVIEW.md).

## Pembaruan 29 Oktober 2026: tema putih dengan aksen warna-warni

Tema aplikasi kembali memakai dasar putih dan aksen utama ungu-biru. Kartu statistik, ikon katalog layanan, kanal beranda, serta nomor alur memakai variasi ungu, biru, hijau, oranye, dan merah muda. Formulir, chat, konsultasi, serta notifikasi tetap memakai tema terang yang selaras. Acuan komponen tersedia di [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md).

## Pembaruan: tautan tetap dan antrean konsultasi mandiri

Pengaturan petugas kini cukup berupa tautan tetap Google Meet. Pengguna memilih jadwal sendiri tanpa slot yang diterbitkan staf. Antrean kosong langsung menuju Meet; antrean berisi pengguna menampilkan halaman menunggu dengan nomor dan tombol membuka ruang. Giliran diperbarui setelah petugas menyelesaikan konsultasi. Formulir dan halaman antrean telah diperiksa di browser sesuai tema yang ada. Rincian terbaru ada di [GOOGLE_MEET.md](GOOGLE_MEET.md); catatan penerbitan slot di bawah merupakan riwayat implementasi sebelumnya.

## Pembaruan: jadwal konsultasi dan Voice to Chat petugas

Google Meet kini menggunakan tautan dan slot waktu yang disediakan PTSP satker. Masyarakat memilih tanggal/waktu dan topik; jadwal mendatang diverifikasi petugas, slot yang sedang berlangsung langsung diarahkan ke Meet, dan petugas menandai konsultasi selesai. Voice to Chat mendukung pemilihan percakapan oleh petugas sesatker. Rincian ada di [GOOGLE_MEET.md](GOOGLE_MEET.md); alur tempel tautan oleh masyarakat pada catatan lama di bawah telah digantikan.

## Pembaruan: routing chat ke PTSP satker

Pendaftar kini langsung disiapkan percakapan untuk PTSP satkernya. Petugas sesatker menggunakan kotak masuk bersama; pusat dapat memantau tanpa membalas lintas satker. Migrasi riwayat dan pengujian kelima satker telah selesai. Rincian tersedia di [CHAT_PTSP.md](CHAT_PTSP.md). Temuan alur/otorisasi chat pada analisis awal di bawah merekam kondisi sebelum perbaikan ini.

## Pembaruan: Google Meet

Fitur jadwal baru telah beralih ke Google Meet. Menu, formulir, daftar meeting, notifikasi, serta tombol bergabung di dashboard sudah disesuaikan. Database lokal telah menjalankan migrasi ke tabel `meetings`; data pengguna tetap dipertahankan. Jadwal menggunakan tautan nyata yang disalin penyelenggara dari Google Meet. Hak akses meeting kini dibatasi pada peserta, petugas yang ditugaskan, dan staf pemantau. Rincian penggunaan dan batas integrasi tersedia di [GOOGLE_MEET.md](GOOGLE_MEET.md). Bagian analisis awal di bawah merekam kondisi sebelum perubahan ini.

## Pembaruan: perbaikan database dan verifikasi login

Pada 27 Oktober 2026, setelah analisis awal, error login `SQLSTATE[HY000] [1049] Unknown database 'ptsp_online'` telah diperbaiki dengan membuat database `ptsp_online` pada MariaDB lokal dan menjalankan `php artisan migrate --force --no-interaction`. Seluruh 9 migrasi berhasil dan berstatus sudah dijalankan. Charset database adalah `utf8mb4` dengan collation `utf8mb4_unicode_ci`.

Database `pacefix_ptsp` yang ditemukan pada server yang sama digunakan proyek `C:\xampp\htdocs\pacefix` dan memiliki struktur berbeda; database tersebut tidak diubah. Tidak ditemukan dump SQL di proyek PTSP ini. Database baru belum diisi akun petugas, katalog layanan, atau data demo; `DatabaseSeeder` belum dijalankan.

Verifikasi HTTP dilakukan melalui `http://localhost/ptsp_online/public` dengan cookie session dan token CSRF nyata: halaman welcome 200, pengecekan nomor baru mengarah ke pendaftaran, pendaftaran mengarah ke dashboard 200, logout berhasil, dan login ulang nomor tersebut kembali membuka dashboard 200. Halaman layanan, chat, meeting, dan call center juga mengembalikan 200 dalam session pengguna uji. Akun fiktif pengujian sudah dihapus setelah selesai; tidak dibuat akun atas identitas pengguna dari tangkapan layar.

Perbaikan ini menyelesaikan masalah database yang hilang. Temuan keamanan dan integrasi pada analisis di bawah tetap merupakan pekerjaan lanjutan; hasil pemeriksaan database pada bagian berikut merekam kondisi sebelum perbaikan ini.

## Kesimpulan

Aplikasi merupakan monolit Laravel untuk layanan PTSP Pengadilan Tinggi Agama Papua Barat dan empat Pengadilan Agama: Sorong, Manokwari, Kaimana, dan Fak-Fak. Struktur layanan, chat, penjadwalan konsultasi, kontak telepon, dan monitoring satker sudah tersedia. Fondasinya dapat dilanjutkan, tetapi belum layak dianggap siap produksi: autentikasi masyarakat dan otorisasi beberapa tindakan perlu diperbaiki, sedangkan integrasi panggilan dan Zoom belum merupakan layanan komunikasi yang berfungsi penuh.

Analisis mencakup pembacaan kode, boot aplikasi, daftar route, status migrasi, tes bawaan, pemeriksaan sintaks, dan reproduksi perilaku controller menggunakan SQLite di memori. Database aplikasi tidak dibuat atau diubah. Pengujian tampilan interaktif, perangkat mikrofon, panggilan nyata, dan Zoom nyata belum dilakukan.

## Teknologi dan kondisi lingkungan

| Komponen | Hasil pemeriksaan |
| --- | --- |
| Backend | Laravel Framework 7.30.7, Eloquent, autentikasi session |
| PHP yang dijalankan CLI | 7.4.33, `C:\xampp\php\php.exe` |
| Tampilan | Blade, CSS khusus, JavaScript biasa; beberapa pustaka dimuat dari CDN |
| Pipeline aset | Laravel Mix 5; sumber awal Bootstrap 4 masih tersedia |
| Database terkonfigurasi | MySQL, database `ptsp_online` |
| Status koneksi database | Server merespons, tetapi database tersebut tidak ditemukan (`SQLSTATE HY000 / 1049`) |
| Session / antrean / broadcast | File / sync / log pada konfigurasi lokal |
| Konfigurasi waktu / bahasa | `UTC` / `en` pada `config/app.php` |
| Dependensi | Folder `vendor` tersedia; `node_modules` dan lockfile npm/yarn tidak ditemukan |
| Versi kode | Direktori saat ini tidak terdeteksi sebagai repository Git |
| Dokumentasi awal | README masih merupakan README bawaan Laravel |

Laravel 7 berakhir dukungan keamanannya pada 3 Maret 2021. PHP 7.4 berakhir dukungan pada 28 November 2022. Rencanakan peningkatan secara bertahap setelah alur penting memiliki pengujian regresi.

Referensi resmi: [kebijakan dukungan Laravel 7](https://laravel.com/docs/7.x/releases), [versi PHP yang tidak didukung](https://www.php.net/eol.php).

## Alur dan struktur aplikasi

1. Pengunjung membuka landing page `/`.
2. Masyarakat masuk melalui `/welcome`: memilih satker dan memasukkan nomor telepon. Akun yang cocok langsung diloginkan; nomor baru diarahkan ke pendaftaran nama dan nomor.
3. Staf memakai `/staff-login`, lalu form login email/password Laravel. Registrasi email/password umum juga masih aktif melalui `Auth::routes()`.
4. Setelah login, pengguna dapat mengakses dashboard, katalog layanan, chat, mode suara, meeting, dan call center.
5. Penugasan chat memilih operator/admin satker, mengutamakan yang ditandai online, lalu menggunakan staf pemantau sebagai cadangan.

| Bagian | Berkas utama | Perilaku saat ini |
| --- | --- | --- |
| Routing | `routes/web.php` | Route publik, autentikasi, dan fitur PTSP |
| Identifikasi masyarakat | `GuestController`, middleware `AutoLogin` | Middleware sebenarnya mengarahkan pengunjung ke identifikasi; nama AutoLogin tidak lagi menggambarkan perilakunya |
| Hak akses | `app/User.php` | Role `admin`, `operator`, `user`; flag `can_monitor`; satker berupa string `work_unit` |
| Dashboard | `DashboardController` | Statistik global, aktivitas pengguna, monitoring staf per satker |
| Layanan | `ServiceController` | Daftar layanan aktif dan tambah layanan oleh admin yang memiliki `can_monitor` |
| Chat | `ChatController`, view chat | Pesan teks, polling tiap 3 detik, penanda dibaca; belum ada endpoint tutup/transfer room |
| Akses suara | `public/js/voice.js`, view voice | Web Speech API bahasa Indonesia mengubah suara menjadi teks; bukan transmisi audio |
| Notifikasi | `NotificationController`, layout utama | Polling tiap 5 detik untuk staf; pesan belum dibaca dan meeting mendatang |
| Meeting | `ZoomController` | Penyimpanan jadwal, tautan demo, serta percobaan API Zoom dengan JWT lama |
| Call center | `CallCenterController`, `public/js/call.js` | Masyarakat mendapat tautan `tel:`; antarmuka panggilan staf mensimulasikan tersambung dan menyimpan log |

Tidak ditemukan modul manajemen akun staf/satker, alur unggah berkas nyata, alur permohonan layanan beserta status/proses persetujuannya, maupun audit aktivitas khusus. Katalog layanan saat ini terutama menjadi konteks konsultasi chat.

## Data dan batas hak akses

Enam entitas bisnis utama:

- `users`: identitas, role, satker, nomor telepon, flag online dan monitoring.
- `services`: nama, deskripsi, kategori, ikon, status aktif.
- `chat_rooms`: masyarakat, petugas yang ditugaskan, layanan opsional, status buka/tutup.
- `chat_messages`: ruang, pengirim, isi, tipe pesan, status dibaca.
- `zoom_meetings`: masyarakat, petugas opsional, topik, jadwal, durasi, tautan, status.
- `call_logs`: pemanggil, penerima, durasi, tipe, status.

Relasi dan foreign key sudah didefinisikan dalam migrasi. Satker belum memiliki tabel dan ID tersendiri; daftar satker berasal dari `config/ptsp.php`. Pemantauan pusat diizinkan untuk admin/operator dengan `can_monitor`, sedangkan pengelolaan master mensyaratkan admin sekaligus `can_monitor`. Pemeriksaan akses masih tersebar di controller, tanpa policy khusus yang terdaftar.

## Temuan prioritas

### 1. Kritis: nomor telepon dapat dipakai mengambil alih identitas

`GuestController.php:29` mencari pengguna berdasarkan nomor dan satker tanpa membatasi role, kemudian `:41` memanggil `Auth::login()` tanpa bukti kepemilikan nomor. Jalur masyarakat tidak menggunakan OTP atau verifikasi lain. Registrasi masyarakat juga menjadikan nomor telepon sebagai password (`:76`).

Reproduksi pada akun fiktif di SQLite menunjukkan jalur tersebut dapat meloginkan akun operator yang memiliki nomor numerik sesuai validasi dan satker yang cocok. Ini bukan klaim bahwa seluruh akun seed otomatis dapat dimasuki lewat jalur tersebut: beberapa nomor seed memakai tanda hubung, sedangkan validasi form menerima angka.

Perbaikan: pisahkan identitas masyarakat dan staf secara tegas, batasi pencarian role masyarakat, dan gunakan autentikasi yang membuktikan kepemilikan akun. Nomor telepon tidak boleh menjadi password yang dapat ditebak.

### 2. Tinggi: tindakan meeting dan panggilan belum memeriksa kepemilikan

`ZoomController::join()` dan `destroy()` hanya mengambil meeting berdasarkan ID. `CallCenterController::endCall()` juga tidak mengecek apakah pengguna merupakan peserta atau memiliki hak yang sesuai. `call()` hanya memastikan penerima ada, tanpa batas role/satker. Durasi penutupan panggilan belum divalidasi.

Uji controller di database sementara mengonfirmasi pengguna lain dapat mengambil redirect join, membatalkan meeting, dan mengakhiri panggilan yang tidak terkait dengannya. Pembatasan daftar di UI tidak melindungi endpoint tindakan.

Perbaikan: terapkan policy dan cakupan satker secara konsisten untuk daftar, detail, dan mutasi; definisikan secara terpisah hak memantau dan hak mengubah data.

### 3. Tinggi: jalur rendering pesan memungkinkan XSS

`resources/views/chat/show.blade.php:86` dan `:116` memasukkan isi pesan langsung ke `innerHTML`. Layout notifikasi juga merangkai nama pengirim/topik meeting menjadi HTML (`resources/views/layouts/app.blade.php:245–261`). Data ini berasal dari input pengguna.

Pembacaan kode menunjukkan HTML dari pengguna dapat ditafsirkan oleh browser pada jalur AJAX, meskipun rendering awal Blade memakai escaping. Eksekusi payload di browser belum diuji. Gunakan elemen DOM dengan `textContent` untuk data pengguna.

### 4. Tinggi untuk pengembangan: build dapat menimpa aset khusus

`public/js/app.js` berisi fungsi khusus aplikasi, termasuk `ajaxRequest()`, sidebar, dan animasi; `public/css/app.css` berisi stylesheet khusus. Namun `webpack.mix.js:14` membangun ke nama berkas yang sama dari sumber `resources` yang masih berupa scaffold Bootstrap.

Menjalankan build dengan sumber sekarang berpotensi menghilangkan fungsi dan tampilan khusus. Pindahkan sumber khusus ke pipeline yang benar atau pisahkan nama output sebelum menggunakan `npm run dev`/`production`.

### 5. Menengah: daftar chat dan izin detail tidak konsisten

`ChatController::index()` memasukkan chat masyarakat satu satker meskipun ditugaskan kepada operator lain. `authorizeRoomAccess()` hanya mengizinkan operator non-pemantau yang menjadi `admin_id` room tersebut.

Reproduksi: room tampil pada daftar operator lain di satker yang sama, tetapi saat dibuka menghasilkan HTTP 403. Notifikasi juga memakai cakupan satker sehingga bisa mengarah ke chat yang tidak dapat dibuka. Tentukan apakah pelayanan dilakukan per operator atau melalui antrean bersama, lalu seragamkan query dan policy.

### 6. Menengah: komunikasi belum terintegrasi penuh

- Panggilan staf menggunakan timer untuk menampilkan status tersambung; belum ditemukan transport media/signaling WebRTC. Tautan telepon masyarakat membuka aplikasi telepon perangkat dan tidak mengonfirmasi panggilan atau otomatis mencatat riwayat di aplikasi.
- Zoom membuat ID/password acak sebelum mencoba API. Kegagalan API dapat tetap meninggalkan tautan demo dan pesan sukses. Implementasi JWT app lama telah dihentikan oleh Zoom sejak 8 September 2023; perlu Server-to-Server OAuth atau OAuth sesuai kebutuhan.
- Pembatalan meeting hanya mengubah status lokal; belum membatalkan meeting melalui API Zoom.

Referensi: [penghentian JWT app Zoom](https://developers.zoom.us/changelog/platform/deadline-change-for-jwt-app-type-deprecation/).

### 7. Menengah: keandalan chat, monitoring, dan deployment

- Pengiriman chat langsung menampilkan pesan sebelum server mengonfirmasi. Kegagalan hanya dicatat di console. Cursor `lastMessageId` langsung diganti ID pesan terkirim sehingga pesan masuk di antaranya berpotensi terlewati oleh polling.
- URL AJAX chat, redirect mode suara, dan panggilan memakai path absolut root seperti `/chat/...`; perlu diuji/diperbaiki jika deployment memakai subfolder `ptsp_online/public`.
- `is_online` ditetapkan pada seed/registrasi masyarakat, tetapi belum ada mekanisme heartbeat atau pembaruan konsisten saat login/logout. Indikator dan pemilihan staf dapat memakai status lama.
- Dashboard monitoring menjalankan tiga query hitungan per staf. Daftar chat/meeting belum dipaginasi, dan `unreadCount()` memicu query per room, bahkan dipanggil dua kali untuk room yang memiliki pesan belum dibaca.
- Statistik utama dashboard dihitung global untuk semua pengguna; tentukan apakah cakupan global memang dibutuhkan atau harus mengikuti satker/pengguna.
- Zona waktu aplikasi masih UTC sementara layanan berada di WIT. Penyimpanan dan tampilan jadwal perlu memiliki aturan konversi yang jelas.
- Seeder berisi password demo yang seragam, chat/meeting contoh masih membahas IMB, dan `service_id` contoh ditulis sebagai angka tetap. Menjalankan seed ulang memperbarui password akun seed, menonaktifkan layanan di luar daftar seed, dan menambah contoh chat/meeting baru.
- `.env` merupakan konfigurasi lokal dengan debug aktif. Saat deployment, arahkan document root ke `public` dan gunakan konfigurasi produksi. Konfigurasi Apache saat ini belum diaudit.

## Verifikasi yang telah dilakukan

| Pemeriksaan | Hasil dan batasannya |
| --- | --- |
| `php artisan --version` | Berhasil, Laravel 7.30.7 |
| `php artisan route:list --compact` | Berhasil; route aplikasi terdaftar |
| `php artisan migrate:status` | Gagal karena database `ptsp_online` tidak ditemukan; tidak menjalankan migrasi pada MySQL |
| PHPUnit bawaan | Lulus: 2 tes, 2 assertion; hanya halaman `/` berstatus 200 dan assertion boolean; ada peringatan skema XML lama |
| Lint PHP | 73 berkas pada app, routes, config, database, tests lulus pemeriksaan sintaks |
| Lint JavaScript | `public/js/app.js`, `call.js`, `voice.js` lulus `node --check` |
| Migrasi terisolasi | Seluruh migrasi dapat dijalankan ke SQLite `:memory:`; belum membuktikan migrasi MySQL berhasil |
| Reproduksi akses | Login operator melalui jalur nomor, akses meeting/panggilan pengguna lain, serta daftar chat yang berujung 403 terkonfirmasi pada controller |

Reproduksi akses dijalankan langsung pada controller menggunakan akun fiktif; ini memeriksa logika aplikasi, bukan pengujian HTTP/browser menyeluruh. Tidak ada panggilan API Zoom atau telepon nyata yang dilakukan. Status produksi, data lama, backup database, dan visual semua halaman belum diverifikasi.

## Urutan pengembangan yang disarankan

1. **Baseline lokal:** siapkan version control dan backup; pastikan apakah database lama perlu dipulihkan atau database baru memang diinginkan. Selanjutnya verifikasi konfigurasi dan migrasi MySQL. Hindari `migrate:fresh` pada data yang ingin dipertahankan.
2. **Keamanan dasar:** perbaiki autentikasi masyarakat/staf, policy meeting/panggilan/chat, serta rendering pesan dan notifikasi. Tambahkan pengujian lintas pengguna, role, dan satker.
3. **Alur layanan:** tentukan antrean bersama atau penugasan individual, mekanisme ambil/tutup/transfer chat, status permohonan jika dibutuhkan, dan status kehadiran petugas.
4. **Aset dan pengalaman pengguna:** benahi sumber build, URL subfolder, penanganan gagal kirim, konsistensi tampilan untuk operator, serta zona waktu WIT. Uji tampilan di desktop dan ponsel.
5. **Integrasi nyata:** implementasikan OAuth Zoom dengan kegagalan yang jelas dan tentukan kebutuhan panggilan: telepon perangkat, tautan layanan luar, atau komunikasi langsung di browser.
6. **Pemeliharaan:** tambah pagination dan agregasi query, pisahkan data demo dari data master, dokumentasikan instalasi, lalu tingkatkan PHP/Laravel bertahap dengan pengujian regresi.

Dokumen ini merupakan baseline analisis. Kode fitur dan konfigurasi aplikasi belum diperbaiki dalam tahap ini.
