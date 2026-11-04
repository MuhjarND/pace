# Operasional PTSP

## Menu dan hak akses

| Pengguna | Menu | Hak |
| --- | --- | --- |
| Admin dengan akses lintas satker | Akun pengguna, Satuan kerja | Membuat/memperbarui akun, peran, penempatan, status aktif; menambah dan mengatur penerimaan satker |
| Admin atau petugas PTSP | Jam layanan | Mengatur satkernya sendiri; parameter satker dari klien tidak menentukan target perubahan |
| Admin atau petugas PTSP | Antrean pelayanan | Verifikasi/tolak, layani, dan selesaikan konsultasi satkernya sendiri |
| Petugas dengan akses monitoring | Antrean pelayanan | Memilih satker untuk membaca; tidak dapat bertindak pada antrean satker lain |
| Masyarakat | Google Meet → Jadwalkan konsultasi | Memilih tanggal/waktu yang tersedia di satker akun; melihat riwayat sendiri |

Route utama: `/admin/accounts`, `/admin/work-units`, `/operations/hours`, `/operations/queue` relatif terhadap direktori public aplikasi. Tautan Google Meet tetap dikelola pada `/meetings/manage`.

## Akun dan satuan kerja

Admin membuat akun dengan email unik, peran, satuan kerja, dan kata sandi minimal 10 karakter. Nomor telepon opsional, unik bila diisi. Pada edit, kedua isian kata sandi dikosongkan untuk mempertahankannya. Kata sandi disimpan sebagai hash dan tidak masuk jejak perubahan.

Admin dengan `can_monitor` dapat mengelola administrasi pusat. Operator dengan flag yang sama hanya memonitor lintas satker. Masyarakat tidak dapat diberi flag tersebut. Admin tidak dapat menghilangkan akses administrasi akun yang sedang dipakai; setidaknya satu admin pusat harus tetap aktif.

Perubahan akses atau kata sandi melalui pengelolaan akun menaikkan `auth_version`, mengganti remember token, dan melepas penugasan aktif agar tim satker tetap dapat melayani. Sesi lama berakhir pada permintaan berikutnya. Akun nonaktif ditolak pada login email, login nomor HP, dan akses sesi. Akun serta riwayat tidak dihapus.

Daftar satker berasal dari tabel `work_units`, bukan daftar tetap di konfigurasi. Satker baru langsung tersedia untuk pendaftaran, penempatan akun, chat, dan pengaturan tautan Meet. Kode `PTSP-001` dan seterusnya pada migrasi adalah kode internal aplikasi, bukan klaim kode resmi pengadilan. Nama menjadi identitas tetap setelah dibuat karena aplikasi lama menyimpannya pada riwayat. Kode internal, alamat, telepon, dan status dapat diperbarui.

Satker nonaktif menolak pendaftaran/konsultasi baru. Pengguna lama tetap dapat login dengan satkernya dan konsultasi yang sudah ada tetap dapat diselesaikan. Satker nonaktif tidak ditawarkan untuk penempatan akun baru. Menonaktifkan satker tidak menonaktifkan akun petugasnya.

## Jam layanan dan kuota

Pengaturan awal tidak menerapkan batas jam. Petugas mengisi hari buka, jam buka/tutup, pasangan awal/akhir istirahat opsional, lalu mencentang **Terapkan jam layanan pada penjadwalan baru** dan menyimpan. Tidak ada jam kerja yang ditetapkan otomatis untuk satker lama.

- Waktu pengguna dan petugas menggunakan WIT (`Asia/Jayapura`); timestamp database menggunakan UTC.
- Jam buka termasuk, jam tutup tidak termasuk. Awal istirahat tidak termasuk, akhir istirahat termasuk.
- Pilihan jam berjarak 15 menit. Aturan membatasi waktu mulai, bukan menjamin durasi/akhir konsultasi.
- Penjadwalan tersedia sampai 365 hari ke depan; **Sekarang** hanya untuk hari ini dan periode layanan yang tersedia.
- Kuota harian opsional, 1–500 konsultasi. Kosong berarti tidak dibatasi.
- Pending, aktif, dan selesai ikut kuota tanggal jadwal. Dibatalkan/ditolak melepaskan kuota; nomor antrean tidak digunakan kembali.
- Tanggal tutup khusus mengesampingkan jam mingguan. Hapus tanggal tutup melalui dialog konfirmasi untuk memakai jadwal mingguan kembali.
- Jam, kuota, serta hari tutup tidak berlaku bila pengaturan belum diterapkan. Perubahan tidak membatalkan jadwal lama atau menghalangi penyelesaiannya.

Ketersediaan dimuat ulang setelah perubahan tanggal. Jika gagal, waktu/tombol pengajuan dinonaktifkan dan tersedia tombol coba lagi. Server tetap memeriksa unit aktif, jam, istirahat, hari tutup, rentang tanggal, dan kuota saat POST. Pemeriksaan/penambahan tiket diserialkan dengan kunci baris satker dalam transaksi yang sama.

## Antrean petugas

Empat ringkasan menampilkan pemohon menunggu, sedang dilayani, perlu verifikasi, dan selesai hari ini. Tab **Antrean aktif** mencakup konsultasi yang sudah waktunya serta antrean dari hari sebelumnya. Tab **Perlu verifikasi** mencakup semua pengajuan pending. Tab **Riwayat** memakai tanggal jadwal WIT. Daftar dipaginasi 15 data.

Petugas hanya dapat memulai/menyelesaikan kepala antrean. **Buka Meet** membuka tautan satker di tab baru; tindakan **Layani** dan **Selesai** memperbarui status aplikasi. Status Google Meet tidak disinkronkan otomatis. Lama menunggu dihitung sejak jadwal; lama konsultasi menggunakan `started_at` yang dicatat saat pertama mulai. Data lama tanpa waktu mulai menampilkan “Belum tercatat”. Tidak ada estimasi durasi tunggu yang dijanjikan.

Pembaruan otomatis tiap 10 detik saat tab terlihat menggunakan fragment Blade yang sama dengan render awal. Form/fokus/dialog yang sedang digunakan tidak diganti. Kegagalan mempertahankan data terakhir dan menampilkan tindakan coba lagi; perubahan sesi/izin meminta masuk kembali. Nama dan topik hanya dikirim ke petugas/pemantau yang berhak, bukan ke endpoint antrean anonim masyarakat.

## Migrasi dan pemeriksaan

Migrasi `2026_10_31_000011_add_ptsp_operations.php` membuat tabel satker, jam layanan, hari tutup, dan jejak administrasi; menambahkan status aktif/versi sesi pengguna serta waktu mulai konsultasi. Semua akun lama tetap aktif, konfigurasi jam tetap belum diterapkan. Nama satker dari konfigurasi/riwayat dipertahankan.

Database dicadangkan ke `storage/app/private/ptsp-before-operations-20261031-133752.sql` sebelum migrasi lokal. Tidak menjalankan `migrate:fresh` atau seeder ulang pada database aplikasi.

Pengujian otomatis: `C:/xampp/php/php.exe vendor/phpunit/phpunit/phpunit`. Pengujian fitur baru berada pada `tests/Feature/PtspOperationsTest.php`; alur chat dan Google Meet tetap diuji oleh suite sebelumnya. Data uji browser dibuat terpisah pada satker sementara dan dibersihkan berdasarkan ID setelah verifikasi; tidak membuka konferensi Google Meet eksternal.
