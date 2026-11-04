# UI PTSP — 31 Oktober 2026

## Arah dan perubahan

Meja layanan digital dengan dasar putih dan loket berwarna. Identitas ungu-biru serta variasi hijau/oranye/merah muda dipertahankan. Typography Plus Jakarta Sans untuk judul dan Inter untuk isi; fallback sistem tetap tersedia. Dashboard memuat konteks satker, angka langsung dari server, pintasan kanal dan agenda. Katalog memiliki filter kategori tetap, URL yang memulihkan pencarian, hasil terhitung, clear dan no-results. Beranda menampilkan alur akses satker; informasi perizinan generik diganti dengan informasi layanan pengadilan.

Shared CSS memperbaiki tombol, input, tabel, chat, kartu dan konsultasi. Shared JS menangani label, validasi inline, reveal kata sandi, textarea auto-grow, modal dan submit busy. Drawer seluler memakai inert, trap keyboard, Escape dan pengembalian fokus. Hiding shell halaman publik kini ditetapkan saat render, bukan setelah halaman tampil. Partikel, tween angka dan animasi masuk bertingkat dihapus.

## Kepemilikan dan drift

| Sebelumnya | Keputusan | Pemilik |
|---|---|---|
| Variasi katalog mengikuti urutan | Warna mengikuti kategori layanan | public/css/app.css |
| Label/error/modal dibuat lokal | Enhancement bersama, server tetap otoritatif | public/js/ui.js |
| Drawer hanya transform | Isolasi fokus + backdrop pada seluler | public/js/app.js |
| Filter katalog tanpa pemulihan | Query/kategori dalam URL, clear/no-results | public/js/catalog.js |
| Font/radius tersebar | Runtime token kanonis, dokumen mencerminkan | DESIGN.md dan app.css |

Tidak ada perubahan hak akses, database, urutan antrean atau integrasi Google Meet. `DESIGN.md` dan `UX-CONTRACT.md` menjadi acuan lanjutan. Sumber aset aktual tetap `public/`; scaffold webpack lama tidak dipakai untuk build UI ini.

## Verifikasi aktual

- PHPUnit: **34 tests, 224 assertions**, lulus. Warning schema XML PHPUnit lama masih ada.
- `designmd lint DESIGN.md`: **0 error, 0 warning**.
- `audit_project.py . --mode strict`: **0 finding**; JSON di `premium-audit.json`.
- `node --check` untuk app.js, ui.js, catalog.js, voice.js: lulus.
- `php artisan view:cache`: seluruh Blade berhasil dikompilasi.
- Pencarian native alert/confirm/prompt di public/js dan resources/views: tidak ditemukan.
- Browser: login kosong menampilkan pesan Indonesia dan memfokuskan email; login akun petugas dan admin berhasil.
- Katalog: kategori kepaniteraan menghasilkan 4/20, query tanpa hasil menghasilkan 0/20, clear memulihkan 4/20; muat ulang mempertahankan kategori dari URL.
- 390×844: katalog satu kolom, drawer terbuka dengan fokus tombol tutup, background inert, Escape mengembalikan fokus tombol menu. Formulir konsultasi tidak meluap horizontal; judul satker panjang membungkus.
- Desktop: dashboard, katalog, beranda dan pengaturan konsultasi diperiksa. Dropdown native monitoring dibuka dan ditutup dengan Escape.
- Admin: modal tambah layanan memfokuskan nama; submit kosong memberi error inline; Escape mengembalikan fokus ke Tambah layanan. Tidak menyimpan layanan percobaan.
- Runtime: scrollbar-color terhitung dari token pada root; elemen animate__animated memiliki animation-name none. Konsol tidak menunjukkan error pada halaman yang diperiksa.
- Gambar: `screenshots/ui-premium-dashboard.png`, `screenshots/ui-premium-beranda.png`.

## Batas bukti

Auditor skill memeriksa JS/CSS; suffix Blade PHP tidak dianalisis auditor tersebut. Blade diperiksa terpisah melalui kompilasi, pencarian statis dan browser. Pemeriksaan ini bukan sertifikasi WCAG penuh: screen reader, perangkat sentuh fisik, forced-colors, emulasi OS reduced-motion, offline dan zoom 200% belum diuji langsung. Aturan CSS reduced-motion/forced-colors tersedia. Mikrofon dan panggilan Google Meet eksternal tidak dijalankan. Alur perubahan data/regresi izin diperiksa oleh suite Laravel; review visual tidak membuat atau menghapus data layanan/konsultasi.

## Perluasan seluruh halaman · 3 November 2026

Tiga patokan Image Gen disimpan di docs/design: public-interior-reference.png, workspace-reference.png dan access-reference.png. Brief dan cakupan route ada di interior-ui.md. Setelah implementasi, seluruh patokan dan screenshot terbaru dibuka berpasangan dengan view_image. Perbandingan mencakup komposisi/pemisahan panel, palet, hierarki tipografi, geometri kontrol, ikon, kedalaman, ritme halaman dan responsivitas. Refinement: ukuran judul form, warna/kondisi navigasi aktif, tinggi panel brand login, baris kanal dengan penanda kuning, serta radius tombol bantuan.

Perbedaan praktis yang dipertahankan: header/footer mengikuti patokan beranda; glyph mengikuti Font Awesome aplikasi; reveal menggunakan primitive teks Lihat; data/tanggal mengikuti server; panel monitoring hanya muncul untuk admin sesuai izin yang sudah ada. Ukuran teks petugas lebih rapat untuk tabel dan form operasional. Tidak mengadopsi salinan kebijakan atau menu tambahan dari generator.

Browser IAB/CUA memakai SQLite terisolasi pada port 8017, Fonnte nonaktif, akun dan fixture detail yang jelas berlabel uji. MariaDB utama mengalami crash saat membaca work_units, dengan log InnoDB read 0 byte; tidak dilakukan restore atau migrasi pada database utama. Dengan demikian QA tidak membuktikan kesehatan MariaDB atau panggilan integrasi eksternal. Database dan server QA sementara dihentikan/dibersihkan setelah review; gambar referensi dan bukti render menjadi artefak hasil.

Pemeriksaan desktop 1440×1100 dan ponsel 390×844:

- Publik: perkara, biaya, konsultasi, pengaduan, pelacakan dan detail laporan. Submit perkara kosong memberi error inline dan fokus pengadilan. Tambah pihak biaya tetap berfungsi; pemilihan Meet menampilkan tanggal/waktu serta kondisi disabled. Detail laporan uji dibuka melalui nomor dan kode yang sesuai.
- Petugas: dashboard, antrean, jam layanan, wilayah/tarif, akun (daftar/tambah), satker (daftar/tambah), daftar/tautan Meet, chat, voice, call center, katalog, kotak masuk dan detail laporan PTA. Rute pembuatan Meet untuk petugas tetap dialihkan ke pengaturan sesuai aturan lama. Detail chat diperiksa dengan dua pesan uji.
- Jam layanan semula memperlebar halaman karena posisi label sr-only pada tabel. Setelah wrapper menjadi containing block, scrollWidth dokumen 380px pada viewport 390px; tabel tetap dapat menggulir internal. Halaman lain yang diperiksa juga tidak meluap horizontal.
- Drawer memfokuskan tombol tutup, mengisolasi latar, dan Escape mengembalikan fokus ke Buka menu navigasi. Modal Tambah layanan/Escape mengembalikan fokus ke pemicunya. Tidak menyimpan perubahan layanan atau pengaturan melalui UI.
- Login desktop/ponsel, reveal milik ui.js, tautan konsultasi tamu dan pemulihan akun berbahasa Indonesia diperiksa. Panel brand desktop disembunyikan pada ponsel, sehingga form tetap terbaca. Konsol QA tidak mencatat error/warning.

Verifikasi otomatis: PHPUnit 115 tes/946 assertion lulus; syntax 9 JavaScript dan kompilasi Blade lulus. Lint DESIGN.md diperiksa ulang setelah token panduan ditautkan. Audit premium strict dicoba tetapi Python hanya berupa alias Microsoft Store dan tidak tersedia; audit terbaru tidak diklaim lulus. Tidak mengklaim uji perangkat fisik, pembaca layar penuh, lampiran asli, pengiriman WA atau Google Meet eksternal.

Bukti render final berada di C:/Users/rubik/.codex/artifacts/ptsp-ui-20261103 (case-desktop.jpg, workspace-desktop.jpg, login-desktop.jpg, accounts-desktop.jpg, complaint-mobile.jpg, login-mobile.jpg). Angka pada screenshot petugas berasal dari database uji, bukan statistik lingkungan utama.

## Konsultasi ringkas · 4 November 2026

Pengantar panjang, navigasi layanan kedua, judul form kedua, callout pengaduan dan panduan samping pada konsultasi dihapus. Form terpusat memakai varian citizen-consult-page; petunjuk menjadi details/summary native yang tertutup awal. Persetujuan, konteks wilayah yang belum terverifikasi, label wajib/opsional WhatsApp, jadwal WIT dan tiket tetap ada. Perubahan kecil ini mengikuti sistem warna/komponen yang sama; tidak membuat patokan gambar baru.

PublicAccessTest: 23 tes/192 assertion lulus. Kompilasi Blade dan lint DESIGN.md (0 error/0 warning) lulus. Browser IAB/CUA pada desktop 1440×1100 dan ponsel 390×844: form terlihat langsung, tidak ada overflow halaman (380px pada viewport 390px), petunjuk dapat dibuka/ditutup dengan Enter, validasi kosong fokus pada Pengadilan, perubahan Meet menampilkan jadwal serta mewajibkan WhatsApp/tanggal, dan chat mengembalikan WhatsApp menjadi opsional. Konsol QA tidak mencatat warning/error. Tidak mengirim konsultasi atau WhatsApp. Pengujian memakai SQLite sementara karena halaman MariaDB utama belum dapat dimuat; tidak memulihkan database utama pada perubahan ini. Bukti desktop: C:/Users/rubik/.codex/artifacts/ptsp-simple-20261104/desktop.jpg.

## Website bergambar · 4 November 2026

Patokan Image Gen aktif: docs/design/illustrated/home-reference.png dan consult-reference.png. Brief/prompt lengkap di prompts.json direktori yang sama. Kelima aset produksi berada di public/images/ptsp-*.webp; total 257874 byte, alpha transparan terverifikasi (127 di sudut GD), dimensi tercatat di config/ui.php. Ilustrasi generik, tanpa data atau identitas institusi nyata. Versi hero final memakai rambut alami tanpa aksesori kepala yang tidak diminta, bangunan/peta simbolis dan komposisi utuh.

Patokan dan screenshot browser terbaru dibuka berpasangan melalui view_image. Perbandingan mencakup komposisi hero/form/galeri, palet putih-oranye-peach, tipografi Plus Jakarta/Inter, proporsi dan framing gambar, kontrol serta ritme/ruang antarbagian. Implementasi memakai varian komponen bersama; header/footer tetap konsisten dengan aplikasi, label dropdown tetap terlihat, dan gambar konsultasi tetap ringkas sesuai brief. Aset terpisah memakai komposisi lengkap tanpa crop/overlay; tidak menempel screenshot konsep sebagai UI.

Browser IAB/CUA desktop 1440×1100 dan ponsel 390×844:

- Kelima gambar beranda dimuat dengan ukuran asli yang benar; empat layanan terlihat dan dapat dinavigasi. Galeri ponsel dua kolom, gambar utuh dan judul tetap terbaca. Dokumen 380px pada viewport 390px, tanpa overflow horizontal.
- CTA Cari pengadilan memfokuskan pilihWilayah; submit kosong memfokuskan finder-0 dengan invalid inline. Papua Barat Daya → Kabupaten Sorong → Aimas → Aimas menghasilkan PA Sorong. Hasil dan galeri mempertahankan konteks wilayah; rujukan dan catatan kompetensi tetap tersedia.
- Biaya, perkara/akta, pengaduan, pelacakan, konsultasi dan login diperiksa desktop/ponsel. Semua aset yang diharapkan dimuat; tidak ada overflow halaman. Dashboard menggunakan gambar sambutan kecil; tabel operasional tetap sama. Gambar dashboard disembunyikan pada ponsel.
- Form konsultasi tetap satu panel dengan Petunjuk tertutup awal, pilihan chat/Meet dan persetujuan. Ilustrasi ponsel menjadi thumbnail kecil. Login diuji memakai akun SQLite sementara, bukan kredensial lingkungan utama.

PHPUnit 115 tes/946 assertion lulus; syntax sembilan JavaScript, config/ui.php, kompilasi Blade dan lint DESIGN.md (0 error/0 warning) lulus. Audit premium strict dicoba tetapi tidak dapat berjalan karena Python belum tersedia; tidak diklaim lulus. Tidak mengklaim audit pembaca layar penuh/perangkat fisik.

Lingkungan browser: SQLite sementara di port 8017, snapshot wilayah lokal dan Fonnte nonaktif; tidak menyimpan perubahan pada database utama, tidak mengirim laporan/konsultasi/WA atau membuka Meet eksternal. Kendala MariaDB utama sebelumnya belum dipulihkan pada tugas UI ini. Lingkungan QA sementara dibersihkan setelah pemeriksaan. Bukti hasil yang dipertahankan sebagai deliverable berada di C:/Users/rubik/.codex/artifacts/ptsp-illustrated-20261104: home-desktop.jpg, home-mobile.jpg, consult-desktop.jpg, login-desktop.jpg, dashboard-desktop.jpg.

## PACE identity, header and footer — 4 November 2026

- Generated new P/speech-bubble logo and header/mobile/footer concept with Built-in Image Gen. Exact prompts: docs/design/pace/prompts.json. Production master and optimized alpha assets are under public/images/pace-*.
- Implemented one shared brand partial and config identity across public shell, staff sidebar, login/register and guest welcome; titles and favicon updated. Footer now contains only PACE / PTA Papua Barat. Public service names, sources and permissions stay owned by existing domain code.
- Header nav is the shared service-links partial, with an inline disclosure owner navigation.js at <=1050px. Tested open, Tab from menu to first service, Escape close/return focus, outside click close, service navigation and active state, and focus recovery on desktop-to-mobile breakpoint.
- Browser sizes 1440px, 1100px, 390px and 320px: no horizontal document overflow. Mobile menu rows 48px; login control 44px on narrow view. Icons/assets loaded. Authenticated six-link header fits at 1100px. Login and dashboard checked with one isolated disposable SQLite account, then logged out.
- Console warnings/errors: none in captured browser log. Footer DOM text exactly PACE / PTA Papua Barat. Reference and final browser screenshots directly compared via view_image.
- PHPUnit: 115 tests / 946 assertions pass. Blade view:cache, config lint, JavaScript syntax checks pass. DESIGN.md lint: 0 errors, 0 warnings. Python-based premium auditor remains unavailable in this Windows environment (previously established); not claimed as passed.
- Evidence: C:/Users/rubik/.codex/artifacts/pace-header-20261104/desktop-full.jpg, mobile-menu.jpg, login.jpg, workspace.jpg.
- UI QA uses temporary SQLite and Fonnte disabled because of the previously observed main MariaDB failure; main database recovery is outside this change. Preview server, test DB and helper are removed after verification. No production credentials or session-cookie settings changed.