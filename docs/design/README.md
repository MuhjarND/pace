# Patokan UI PACE PTA Papua Barat

## Acuan aktif

Pengguna memilih konsep E hijau–emas: [spesifikasi aktif](green-gold/README.md) dan [patokan beranda](green-gold/home-reference.png). Konsep oranye dan referensi bertanggal di bawah menjadi riwayat; pemilik perilaku/komponen yang tidak diubah tetap dipakai.

Redesign bergambar 4 November 2026: [patokan dan aset ilustrasi](illustrated/README.md), [beranda](illustrated/home-reference.png), [konsultasi](illustrated/consult-reference.png). Ini acuan aktif untuk komposisi beranda dan penambahan gambar; referensi di bawah menjadi riwayat evolusi desain dan acuan komponen yang tidak diubah.

Perluasan 3 November 2026 untuk halaman selain beranda: [spesifikasi interior](interior-ui.md), dengan patokan [publik](public-interior-reference.png), [petugas](workspace-reference.png), dan [login](access-reference.png). Seluruhnya memakai sistem warna dan tipografi beranda yang sama. Patokan beranda di bawah tetap berlaku untuk beranda.

Permintaan pengguna pada 2 November 2026: buat UI terbaru menggunakan pembuat gambar UI, kemudian jadikan gambar itu patokan implementasi.

[Gambar patokan desktop](portal-desktop-reference.png) dibuat dengan tool Image Gen bawaan, ukuran 1374 × 1145. Ini adalah spesifikasi visual; implementasi tetap berupa Blade/HTML/CSS dengan kontrol dan data nyata. PNG tidak ditempel sebagai tangkapan layar pengganti UI.

Urutan layar yang diikuti:

1. Header putih dengan identitas PTSP Online/PTA Papua Barat, lima kanal dan Login petugas.
2. Hero peach `--surface-orange`: judul dan ilustrasi di kiri, form pencarian pengadilan yang ditinggikan di kanan. Empat dropdown asli berada dalam hero.
3. Rel lima kanal layanan dengan penanda aktif peach dan ikon yang jelas.
4. Panduan tiga langkah horizontal di atas bidang putih.
5. Pita bantuan hitam dengan tombol kuning, lalu footer institusi/wilayah.

Pada ponsel urutan hero menjadi judul → form → ilustrasi. Rel kanal menjadi dua kolom dan panduan menjadi satu kolom. Form memakai tinggi alami halaman; dropdown tetap native dan semua nama isian dipertahankan. Tidak mengunci halaman ke tinggi gambar referensi.

## Sistem visual dan aset

Palet: `#FFFFFF`, `#FF4F00`, `#FFC400`, `#1C1C1C`, teks sekunder `#626262`, hero `#FFF3EB`. Judul memakai Plus Jakarta Sans, isi/kontrol Inter. Sumber token adalah `public/css/app.css`; aturan portal berada di `public/css/public-portal.css`. Teks tombol oranye/kuning hitam untuk menjaga kontras.

Ilustrasi `public/images/court-location.webp` (1536×1024, 107.014 byte) dipakai ulang sebagai aset terpisah. Latar transparan, tanpa overlay, teks atau identitas bangunan satker. Isi peta adalah simbol lokasi, bukan data geografis. Form dan kanal memakai Font Awesome yang sudah dimuat aplikasi; varian ikon mengikuti metafora patokan dan keluarga ikon aplikasi.

Pemilik implementasi: `resources/views/public/finder.blade.php`, `resources/views/layouts/public.blade.php`, serta partial `public/partials/service-links.blade.php` dan `public/partials/region-picker.blade.php`. Validasi/feedback tetap dimiliki `ui.js`; dropdown bertingkat tetap dimiliki `public-portal.js`. Kontrak data/izin mengacu ke `docs/AKSES_PUBLIK.md` dan `docs/PENGADUAN.md`.

## Brief final Image Gen

UI-mockup portal pengadilan Indonesia yang lengkap dan siap diterapkan. Hero peach dua kolom: judul “Layanan pengadilan, lebih dekat.” dan ilustrasi pengadilan/peta di kiri, form putih “Cari pengadilan Anda” di kanan. Empat native dropdown Provinsi, Kabupaten / kota, Distrik, Kampung / kelurahan; tiga turunan nonaktif hingga induknya dipilih. Tombol “Cari pengadilan” oranye dengan teks hitam, keterangan sumber Kemendagri 2025/WIT. Header memakai ikon landmark hitam dalam kotak oranye, brand dan lima kanal yang sudah ada, serta Login petugas. Di bawah hero ada rel lima kanal, panduan tiga langkah horizontal, pita bantuan hitam/tombol kuning dan footer institusi/wilayah. Gunakan persis palet dan font di atas; ilustrasi lengkap tidak terpotong; tidak menambahkan menu, metrik, badge, gradasi atau fitur rekaan. Deskripsi Pengaduan menyebut layanan dan gratifikasi. Gambar lengkap menjadi patokan, bukan hanya hero.

## Pemeriksaan kesesuaian

Perbandingan dilakukan terhadap komposisi hero/form, palet, tipografi, ikon/aksi, framing ilustrasi, rel layanan, panduan dan pita bantuan. Perbedaan praktis yang diterima: glyph Font Awesome mengikuti set aplikasi, kontrol native mengikuti browser/OS, kontainer mengikuti viewport, dan gambar 3D ditampilkan lengkap walaupun komposisi raster referensi sangat rapat di bagian bawah.

Tidak ada perubahan kebijakan wilayah, tarif, autentikasi, anonimitas atau hak pembaca pengaduan pada perubahan UI ini.

## Hasil verifikasi

Patokan dan screenshot implementasi dibuka berpasangan melalui view_image. Penyesuaian setelah perbandingan: ukuran headline, skala serta posisi ilustrasi agar tidak menyentuh tautan, posisi navigasi header, jarak rel kanal dan padding footer. Komposisi hero/form, warna, tipografi, ikon/aksi dan urutan bagian mengikuti patokan; perbedaan praktis di atas tetap didokumentasikan.

Browser IAB/CUA diperiksa pada 1374×1145 dan 390×844. Empat pilihan bertingkat Papua Barat Daya → Kabupaten Sorong → Aimas → Aimas menghasilkan PA Sorong, dengan link konteks biaya/konsultasi tetap tersedia. Popup native dibuka, validasi pencarian kosong memfokuskan Provinsi dengan pesan Indonesia, dan ilustrasi ponsel diperiksa saat masuk viewport. Tidak ada overflow halaman desktop; lima kanal tersedia; console tab QA tidak mencatat error/warning.

PHPUnit: 115 tes/946 assertion lulus, kemudian 23 tes/192 assertion PublicAccessTest lulus setelah perbaikan pesan validasi. Syntax sembilan berkas JavaScript, syntax PublicPortalController, kompilasi Blade dan lint DESIGN.md lulus. Audit statis premium strict dicoba tetapi tidak berjalan karena Python belum tersedia; tidak diklaim lulus. Browser/perangkat fisik lain dan audit pembaca layar penuh belum diperiksa.
