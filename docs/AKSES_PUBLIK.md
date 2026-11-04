# Portal masyarakat dan konsultasi tamu

Implementasi Laravel 7; antarmuka memakai CSS/JS publik kanonis, tanpa build Laravel Mix. Portal: `/`, `/cari-pengadilan`, `/perkiraan-biaya`, `/konsultasi`. Petugas: **Wilayah dan biaya** dan **Antrean pelayanan**.

## Wilayah dan pengadilan

`php artisan ptsp:import-regions` memuat snapshot yang dikunci di `database/data/papua-regions.json`: 2 provinsi, 13 kabupaten/kota, 223 distrik, 1.837 kampung/kelurahan (2.075 entri). Impor memeriksa duplikasi, kode, induk dan jumlah, tidak menjalankan SQL unduhan. Sumber olahan MIT: https://github.com/cahyadsn/wilayah, commit d68e8d5516f969d1905d0b2940f20034becb0db7, Kepmendagri 300.2.2-2138/2025. Ini snapshot referensi, bukan klaim pemutakhiran seluruh perubahan administrasi 2026.

`php artisan ptsp:import-court-territories` menambahkan 13 pemetaan berdasarkan SK KMA 19/KMA/SK.OT1.1/I/2026, berlaku 22 Januari 2026, Lampiran II halaman 68–69. Tambrauw diarahkan ke PA Sorong; lima kabupaten Papua Barat selain Fakfak dan Kaimana ke PA Manokwari. Impor mempertahankan keputusan admin yang sudah ada. Sumber: https://jdih.mahkamahagung.go.id/storage/uploads/produk_hukum/2026/2026sk-kma19/KMA/SK.OTl.1/1/2026.pdf . Teks dokumen berhasil dibaca; pengambilan PDF langsung/screenshot sempat 404. Halaman situs PA Manokwari lama yang mencantumkan Tambrauw tidak dipakai untuk mengalahkan SK 2026.

Resolver memilih pemetaan paling spesifik; draft turunan menghentikan pewarisan induk. Wilayah tanpa pemetaan terverifikasi menampilkan perlu konfirmasi PTSP. Hasil merupakan pengadilan yang melayani wilayah, bukan penentuan otomatis kompetensi relatif untuk setiap jenis perkara. Alamat/telepon dari situs resmi diisi hanya jika sebelumnya kosong; jam baru harus dipublikasikan satker.

## Tiket tamu

Nama/panggilan, satker, topik dan persetujuan diperlukan. WhatsApp wajib untuk Meet, opsional untuk chat. Nomor 08 dinormalisasi menjadi 628. Tidak ada OTP dan tidak ada login berdasarkan nomor telepon. Setiap pengajuan mempunyai principal internal acak, password acak, tanpa nomor pada akun; principal ini tidak dapat login sebagai akun biasa dan disembunyikan dari pengelolaan akun permanen.

Chat menggunakan tim dan otorisasi satker yang sudah ada. Tiket lengkap hanya terbuka melalui sesi perangkat yang mengajukan, dengan pemeriksaan server. Tiket berlaku tujuh hari setelah waktu konsultasi (chat: tujuh hari setelah dibuat); sesi browser dapat habis lebih awal sesuai konfigurasi session. Ini belum menyediakan pemulihan lintas perangkat untuk chat. Nomor tiket yang diketahui orang lain tidak memberikan akses. Pengajuan berulang dengan nonce yang sama menggunakan tiket yang sama.

Meet: pengajuan sekarang masuk antrean langsung, pengajuan mendatang menunggu verifikasi. Verifikasi berbeda dari siap dilayani. Setelah waktu dan giliran sesuai, petugas memilih **Panggil tamu**, kemudian **Mulai layani**, lalu **Selesai**. Membuka halaman undangan atau mengunjungi Meet tidak menandai pelayanan dimulai. Antrean yang sudah dipanggil tidak digeser oleh jadwal lama yang baru diverifikasi. Pembatalan/selesai mengakhiri akses undangan.

Undangan WhatsApp mempunyai token acak 64 karakter; hash disimpan untuk pencarian dan token dienkripsi untuk pengiriman. Undangan hanya membuka status dan tombol bergabung, tanpa nama, topik, chat, atau riwayat lain. GET tidak mengubah status; gabung melalui POST dengan CSRF dan pengecekan ulang. Jangan membagikan tautan undangan. Tidak ada pemantauan kehadiran Google Meet melalui API.

Endpoint `/welcome/check` dan pendaftaran nomor lama mengarahkan ke konsultasi tamu. Migrasi mencabut password lama yang sama dengan nomor telepon pada akun `@guest.ptsp`, tanpa menghapus riwayat. Akses akun lama memerlukan password yang diterbitkan ulang admin setelah pemeriksaan pemilik; jangan menggunakan nomor sebagai bukti kepemilikan.

## Fonnte

Token disimpan hanya di `.env`; tidak disalin ke dokumen ini. Konfigurasi `services.fonnte`: `FONNTE_TOKEN`, `FONNTE_ENABLED`, `FONNTE_WEBHOOK_SECRET` acak, `PTSP_PUBLIC_URL`. Pengirim awal satu perangkat pusat; pesan menyebut satker. Pemeriksaan profil perangkat pada pengembangan berhasil; tidak ada pengujian pengiriman nyata ke nomor masyarakat.

Saat masih localhost, panggilan petugas tetap membuka tombol Meet pada tiket, tetapi WA berstatus konfigurasi belum siap. Untuk produksi:

1. Siapkan alamat HTTPS yang dapat diakses masyarakat. Isi `APP_URL` dan `PTSP_PUBLIC_URL` dengan akar aplikasi publik (sertakan subfolder jika ada, tanpa slash akhir), kemudian `php artisan config:clear`.
2. Pada panel Fonnte, atur webhook status ke `{PTSP_PUBLIC_URL}/api/fonnte/status/{FONNTE_WEBHOOK_SECRET}`. Salin nilai dari konfigurasi server secara privat. Endpoint menolak secret salah dan memproses laporan secara idempoten. Jangan publikasikan URL webhook lengkap di dokumentasi atau tangkapan layar.
3. Jalankan Laravel scheduler tiap menit (`php artisan schedule:run`) melalui penjadwal server. Pengiriman langsung setelah panggilan juga berjalan; outbox tetap tersimpan untuk pemulihan.
4. Lakukan uji pengiriman dengan nomor penerima yang disepakati sebelum layanan publik dibuka. Periksa koneksi perangkat, kuota dan laporan Fonnte.

Pesan diterima API belum berarti terkirim. UI membedakan menunggu, diterima Fonnte, terkirim/dibaca bila laporannya eksplisit, gagal, konfigurasi terblokir dan hasil tidak pasti. Callback dini disimpan sampai ID provider tersedia. Permintaan ganda tidak mengirim ulang. Timeout/proses terhenti tidak otomatis diulang karena hasilnya mungkin sudah diproses. Pengulangan manual hanya untuk penolakan definitif atau konfigurasi terblokir sebelum ada ID provider, maksimal tiga percobaan nyata. `preview=false` dan GET tanpa mutasi mencegah pratinjau tautan memulai layanan.

Referensi integrasi: https://docs.fonnte.com/api-send-message/ dan https://docs.fonnte.com/webhook-update-message-status/ .

## Perkiraan biaya

Form publik tidak meminta nama/NIK. Jenis perkara, pengadilan, kedudukan dan wilayah masing-masing pihak menentukan perkiraan. Komponen mendukung biaya tetap, per pihak, dan panggilan berdasarkan radius dengan pengali sesuai SK. Radius memakai wilayah paling spesifik; data tidak tersedia menghasilkan perlu konfirmasi, bukan Rp0. Hanya tarif dipublikasikan, sudah efektif dan belum kedaluwarsa yang dipakai. Sumber dan tanggal berlaku ditampilkan, serta konteks diteruskan ke konsultasi satker yang sama.

**Nominal produksi belum dipublikasikan.** Dokumen publik Sorong 2026 berbeda: SK 19/KPA.W31-A2/KU1/I/2026 menyebut biaya proses Rp150.000, sedangkan lampiran SK bersama 91/KPA.W31-A2/HK2.6/I/2026 memakai Rp100.000; beberapa jumlah tabel juga perlu konfirmasi. Jangan memilih nominal tanpa verifikasi petugas. Sumber dokumen: https://www.pa-sorong.go.id/kepaniteraan/sk-biaya-proses-penyelesaian-perkara/ dan https://www.pa-sorong.go.id/kepaniteraan/radius-biaya-panggilan/ . Ketiga PA lainnya juga memerlukan SK komponen/radius yang terkonfirmasi. Tidak ada angka dummy dipublikasikan sebagai tarif resmi. Admin pusat mengelola pemetaan; petugas hanya mengelola tarif satkernya (admin pusat dapat lintas satker).

## Batas verifikasi

Pengujian otomatis memakai SQLite memori, termasuk HTTP Fonnte tiruan: akses tiket, isolasi satker, verifikasi/waktu/antrean, panggilan idempoten, undangan read-only, pembatalan/selesai, kegagalan kirim dan kalkulasi tarif. Ini tidak menggantikan uji pengiriman Fonnte nyata, konkurensi beban MariaDB, atau validasi legal tarif oleh pengadilan. Jangan menjalankan migrate:fresh pada database aplikasi.
