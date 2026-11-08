# Portal masyarakat dan konsultasi tamu

Implementasi Laravel 7; antarmuka memakai CSS/JS publik kanonis, tanpa build Laravel Mix. Portal: `/`, `/cari-pengadilan`, `/perkiraan-biaya`, `/konsultasi`. Petugas: **Wilayah dan biaya** dan **Antrean pelayanan**.

Pilihan **Konsultasi Bersama Mace (offline)** pada `/konsultasi` mengambil nomor antrean untuk hari ini (WIT), dengan nama/panggilan, topik, dan persetujuan; WhatsApp opsional. Nomor memakai urutan harian per satker yang sama dengan konsultasi Meet, dihitung di dalam transaksi dengan kunci baris satker. Nonce pengajuan mencegah tiket/nomor ganda saat formulir dikirim ulang. Jam layanan, hari tutup, istirahat, dan kuota satker tetap berlaku. Tiket sesi perangkat menampilkan nomor, posisi, status, alamat, serta tautan panduan kunjungan. Antrean Mace dilabeli offline di ruang petugas dan mendukung panggil → mulai layani → selesai, maupun pembatalan. Tidak memerlukan tautan Meet, tidak menerbitkan undangan daring, dan tidak mengirim WhatsApp. Alur pengambilan nomor juga berfungsi tanpa JavaScript.

Panduan `/konsultasi/mace/{id}` menampilkan alamat, jam layanan/istirahat, tanggal tutup mendatang, persiapan, dan petunjuk arah sesuai data pengadilan aktif, serta tautan pengambilan nomor dengan pilihan Mace terisi. Migrasi `2026_11_07_000017_add_consultation_channel_to_meetings` menambah kanal pada antrean; data lama tetap memakai kanal Meet.

## Statistik beranda

Tersedia pilihan **Data layanan** dan **Data contoh**. Dataset contoh disimpan khusus di `statistics_demo_entries`; tidak membuat pengguna, tiket, chat, jadwal, antrean operasional, atau notifikasi. `php artisan ptsp:seed-statistics-demo` memuat ulang 320 entri contoh untuk lima satker secara idempoten. Selama dataset ini tersedia, beranda memilih mode contoh secara default dan menampilkan badge serta keterangan simulasi. Pengunjung dapat beralih ke data layanan asli melalui tab sumber; filter satker dan tab grafik Kanal/Status mempertahankan pilihan sumber. `php artisan ptsp:seed-statistics-demo --clear` hanya menghapus dataset contoh dan mengembalikan sumber bawaan ke data layanan.

Dataset contoh: Google Meet 84, Chat PTSP 172, Mace 64; selesai 272, aktif 38, dibatalkan 10. Jumlah per satker 120/90/48/42/20. Simulasi jarak PP 12–60 km per entri, kecepatan 40 km/jam, konsumsi 12 km/liter dan harga Rp10.000/liter menghasilkan total 11.420 km, 285 jam 30 menit dan Rp9.516.667. Acuan tersebut khusus dataset contoh dan tidak mengubah referensi perjalanan layanan asli.

Beranda dan pencarian pengadilan menampilkan statistik agregat seluruh periode dari `meetings` dan `chat_rooms`, difilter opsional melalui `stats_unit`. Satu jadwal/ruang chat dihitung sekali, bukan jumlah pesan, tiket, akun, atau orang unik. Kanal mencakup Google Meet, Chat PTSP, dan Mace offline. Status selesai berasal dari jadwal `ended` atau chat `closed`, aktif mencakup pengajuan menunggu verifikasi, dan jadwal `cancelled` tetap terlihat tersendiri. Nama, kontak, topik bebas, ID tiket, dan isi chat tidak masuk statistik publik. Keperluan memakai kategori katalog chat dan kategori umum konsultasi PTSP; topik pribadi tidak diklasifikasikan atau dipublikasikan.

Kartu estimasi perjalanan menjumlahkan snapshot rute per konsultasi, untuk semua kanal/status dalam filter satker. Sumber asal adalah kampung/kelurahan domisili yang dipilih pemohon (`origin_region_code`), berbeda dari konteks daerah hukum `region_code`. Pilihan domisili bersifat opsional dan harus mencapai tingkat kampung/kelurahan. Tidak memakai GPS perangkat, alamat rumah, jarak rata-rata global, atau angka dari query URL statistik. Titik asal menggunakan koordinat acuan wilayah; tujuan menggunakan koordinat pengadilan yang dipilih. Perhitungan merupakan estimasi dari wilayah domisili, bukan titik rumah individual.

Admin pusat mengisi koordinat wilayah dan tautan sumbernya melalui **Wilayah dan biaya → Titik acuan domisili**, serta koordinat pintu masuk pengadilan, konsumsi kendaraan (km/liter), dan harga BBM (Rp/liter) melalui **Administrasi → Satker**. Data wilayah bawaan belum menyediakan titik koordinat; acuan tidak diisi dengan angka rekaan. Konsultasi tetap tersimpan jika acuan belum lengkap atau rute tidak ditemukan. Statistik menampilkan jumlah konsultasi yang sudah memiliki rute; data yang belum terukur tidak dihitung sebagai nol perjalanan.

`JourneyRoutingService` meminta rute jalan asal → pengadilan → asal melalui OSRM; jarak dalam meter dan durasi dalam detik sudah mencakup PP, sehingga tidak dikalikan dua lagi. Biaya BBM per konsultasi = jarak PP (km) ÷ acuan km/liter × acuan Rp/liter, dibulatkan ke rupiah. Harga dan konsumsi disimpan bersama hasil rute agar perubahan acuan berikutnya tidak mengubah hasil lama yang sudah lengkap. Lokasi referensi asal disimpan terenkripsi dan tidak diserialisasi ke statistik publik. Statistik hanya memakai jumlah agregat; topik, identitas, tiket, wilayah asal per orang dan koordinat tidak ditampilkan.

`TRAVEL_ROUTING_URL` dan `TRAVEL_ROUTING_PROFILE` mengatur server OSRM di sisi server; konfigurasi awal memakai layanan OSRM publik dengan profil driving. Rujukan API: https://project-osrm.org/docs/v26.5.0/http#route-service . Permintaan rute berjalan setelah transaksi pemesanan selesai; kegagalan jaringan tidak membatalkan konsultasi. Nonce mencegah snapshot/nomor ganda. Gunakan `php artisan ptsp:refresh-travel --limit=100` setelah melengkapi acuan untuk memproses data tertunda atau melengkapi biaya BBM yang belum tersedia. Riwayat tanpa pilihan domisili tidak direkonstruksi. Migrasi 000018/000019 menambahkan referensi dan snapshot perjalanan. Panel acuan estimasi dan tabel tambahan tetap tidak ditampilkan pada beranda.

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
