# Hasil verifikasi akses publik — 1 November 2026

## Hasil

- PHPUnit: **72 tes, 563 assertions, lulus**. Seluruh tes fitur memakai SQLite memori; tes beranda bawaan diperbaiki agar tidak bergantung pada database aplikasi.
- Sintaks 93 file PHP dan 9 file JavaScript: lulus.
- Kompilasi Blade: lulus. Lint DESIGN.md: 0 error/0 warning. Premium audit strict: 0 temuan. Audit statis bukan pengganti pengujian browser.
- MariaDB: migrasi 12, 13, 14 terpasang; 2.075 wilayah dan 13 pemetaan pengadilan tersedia. MySQL lokal yang belum menerima koneksi saat pemeriksaan awal dijalankan kembali; Apache tidak dihentikan.
- HTTP: `.env` dan file kredensial privat ditolak 403; stylesheet portal tersedia 200. Token tidak dimuat pada halaman publik.
- Data QA dengan user ID 21, nama persis `QA Tamu Portal 20261031`, dan flag is_guest dibersihkan bersama tiket/chat miliknya setelah pemeriksaan identitas/relasi dalam transaksi. Data pengguna lain dipertahankan.

## Browser

Browser IAB melalui CUA, tanpa fallback Playwright eksternal. Portal dibuka kembali setelah koneksi database pulih. Pemeriksaan viewport 1440×1000 dan 390×844: tidak ada overflow horizontal; console halaman akhir tanpa error/warning. Form biaya berhasil menambah pihak baru dengan ID/label unik dan fokus pada pilihan pihak. Pada pemeriksaan sebelumnya, dropdown provinsi→kabupaten/kota→distrik→kelurahan berhasil mengarahkan Remu Utara ke PA Sorong; form konsultasi kosong memberi error inline/fokus, kemudian pengajuan chat tamu menghasilkan tiket dan pesan awal pada satker yang sesuai.

Alur Meet, pemisahan verifikasi/panggil/mulai/selesai, pembatasan satker/sesi, antrean, kedaluwarsa, idempotensi, pembatalan, kalkulasi biaya dan kegagalan provider diverifikasi melalui tes HTTP aplikasi. Callback yang tiba sebelum respons pengiriman juga diuji. Google Meet eksternal tidak dibuka dan pesan WhatsApp nyata tidak dikirim. Status profil perangkat Fonnte sempat diverifikasi valid/terhubung pada 31 Oktober 2026; ini bukan jaminan status perangkat saat produksi.

## Perbandingan visual

Konsep: `C:/Users/rubik/.codex/generated_images/01a17bcb-2b86-7d31-a701-3b502c5b0060/exec-e0f01c2e-1ef6-46b1-84f2-3ceef444a630.png`.

Screenshot akhir: `C:/Users/rubik/.codex/artifacts/ptsp-public/portal-desktop-final.png` dan `portal-mobile-final.png` pada folder yang sama. Konsep dan screenshot akhir diperiksa langsung memakai view_image.

Lima titik perbandingan: header horizontal dan navigasi; judul dua baris beserta deskripsi; tiga jalur tugas dengan tab kuning; form dua kolom dan tombol oranye lebar; panel panduan kuning pucat beserta bantuan/footer. Salinan utama di atas fold mengikuti konsep. Implementasi memakai token, huruf dan hierarki visual yang sama, dengan kontrol native dan aliran satu kolom pada ponsel. Form awal sengaja belum memilih provinsi/kota sehingga tidak menyiratkan lokasi pengguna; kontrol turunannya disabled sampai induk dipilih. Ukuran popup/panah mengikuti OS. Perbedaan kepadatan tipografi mengikuti font web serta shell aplikasi yang sebenarnya; tidak diklaim sebagai kecocokan piksel. Tidak ada konten utama yang terpotong atau kontrol yang tertutup.

## Sebelum penggunaan publik

Alamat HTTPS publik dan konfigurasi webhook/scheduler masih diperlukan untuk pengiriman undangan yang dapat dibuka masyarakat. Nominal tarif produksi masih menunggu SK terkonfirmasi; mesin simulasi dan pengelolaannya sudah tersedia. Rincian konflik sumber Sorong, batas snapshot wilayah, serta panduan operasional ada di `docs/AKSES_PUBLIK.md`.
