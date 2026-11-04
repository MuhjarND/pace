# PACE hijau–emas — desain aktif

Pengguna menyetujui konsep E dan meminta penyesuaian halaman lainnya. `home-reference.png` menjadi acuan aktif; menggantikan patokan beranda oranye. Logo/identitas PACE, layanan, sumber dan izin tetap mengikuti sistem yang sudah ada.

## Inventaris dan aturan

- Beranda: header fixed, judul dua warna dan ilustrasi di kiri, form empat dropdown vertikal di kanan. Empat layanan bergambar berjajar di bawah; footer identitas terpusat.
- Salinan mengikuti konsep: judul “Layanan pengadilan, dari mana saja.”, kalimat singkat, heading “Temukan pengadilan Anda”, empat label wilayah dan tombol “Cari pengadilan”. Dua CTA hero serta caption nonesensial dihapus. Sumber hukum/pemetaan pada hasil tetap ada.
- Font dan ruang: Plus Jakarta Sans/Inter; margin awal 48px, gap utama 28px, panel kanan radius 28px, kontrol 48px dan tombol 60px. Batas <=900px menjadi satu aliran; <=540px menggunakan font/form dan galeri dua kolom yang sesuai sentuhan.
- Palet runtime dimiliki app.css. Hijau #15803D, emas #D6A526, teks #183328, putih; hover/ink #166534; sage #F0F7F0 dan panduan #FCF7E8. Putih/hijau kontras 5,02:1; teks gelap/emas 6,01:1. Alias oranye/kuning lama meneruskan token baru.
- Header/disclosure/fixed spacer tetap navigation.js; kontrol dan validasi tetap ui.js; wilayah tetap public-portal.js. Nama route/scope server tidak diganti.
- Halaman biaya, perkara/akta, konsultasi, pengaduan dan tiket, login/register/recovery, serta shell dashboard/katalog/chat/meet/operasional menggunakan token dan aset yang sama. Varian layout produk dipertahankan agar form/tabel tetap sesuai tugasnya.

## Aset

Built-in Image Gen membuat ulang enam aset terpisah mengikuti konsep; brief lengkap pada [asset-prompts.json](asset-prompts.json) dan [logo-final-prompt.json](logo-final-prompt.json). Gambar bukan hasil potongan screenshot UI. WebP dioptimalkan dengan GD, alpha dipertahankan pada ilustrasi.

| Aset | Berkas di public/images |
|---|---|
| Masyarakat dan petugas | pace-hero-green.webp (1400 × 739) |
| Kalkulator | pace-fees-green.webp (384 × 384) |
| Berkas | pace-case-files-gold.webp (384 × 384) |
| Konsultasi | pace-consultation-green.webp (384 × 384) |
| Pengaduan | pace-privacy-green.webp (384 × 384) |
| Logo | pace-mark-green.webp; master pace-logo-green-source.png |
| Ikon aplikasi | pace-favicon-green.png; pace-touch-icon-green.png |

Pemilik manifest config/ui.php; teks identitas tetap HTML. Dua percobaan logo transparan memiliki alpha rusak dan ditolak. Logo final diregenerasi sebagai bidang hijau opak dengan P/percakapan dan aksen emas; pembulatan dilakukan CSS. Ini memperbaiki artefak transparansi, mempertahankan identitas simbol, dan bukan tint CSS pada logo lama. Aset lama dipertahankan sebagai riwayat, tidak lagi dirujuk oleh manifest.

## Verifikasi dan fidelity

Verifikasi dengan IAB pada 1487 × 1058 (dimensi patokan), 1440 × 1100, 1200 × 900, 390 × 844, dan 320 × 800. SQLite pratinjau terisolasi dengan Fonnte dimatikan, tanpa perubahan data masyarakat. Alamat utama localhost/ptsp_online/public/cari-pengadilan juga berhasil dimuat dengan palet dan aset baru; screenshot home-live.png berasal dari alamat utama.

| Pemeriksaan | Hasil |
|---|---|
| Komposisi | Ilustrasi kiri/form kanan, empat pintasan di bawah, footer terpusat mengikuti patokan. Form vertikal memakai kontrol native, bukan gambar. |
| Palet | CSS primary #15803D, gold #D6A526, teks #183328, background putih; tombol utama putih pada hijau. Tidak ada tint/filter gambar lama. |
| Copy | Judul, kalimat singkat, empat label/placeholder wilayah, nama layanan dan footer cocok. Sumber wilayah pada hasil tetap ada; tidak ada rel kanal kedua. |
| Tipografi/kontrol | Plus Jakarta Sans/Inter, judul besar dua baris, field 16px, label terlihat, fokus/error inline. Header beradaptasi pada 1280px agar enam tautan pengguna masuk tidak bertabrakan dengan Login petugas. |
| Media | Lima gambar dan logo termuat, dimensi eksplisit, ilustrasi 3D diregenerasi terpisah dengan gestur/objek/material sesuai konsep. Logo final memakai bidang opak untuk mengatasi alpha rusak; varian P/percakapan tetap terbaca. |
| Responsif | Tidak ada overflow dokumen pada ukuran yang diuji. Ponsel satu aliran dan galeri dua kolom. Header fixed mempunyai spacer/offset tepat. |
| Interaksi | Menu publik buka/Tab/Escape/fokus kembali lulus. Form kosong memfokuskan Provinsi dengan error. Rantai Papua Barat Daya → Kabupaten Sorong → Aimas → Aimas menghasilkan Pengadilan Agama Sorong dan konteks tautan yang benar. |
| Halaman lain | Biaya, perkara/akta, konsultasi dan pengaduan: judul benar, gambar termuat, tema hijau dan tanpa rel duplikat. Login/dashboard, layanan, chat, Google Meet, antrean, jam layanan, akun dan pengaduan PTA berhasil dimuat. |
| Konsol | Tidak ada warning/error aplikasi pada log browser yang diperiksa, termasuk alamat utama. |
| Pemeriksaan kode | 115 tes / 946 asersi lulus; Blade view:cache, lint config dan sintaks JS lulus. DESIGN lint 0 error / 0 warning. Auditor premium Python tidak dijalankan karena runtime Python yang dibutuhkan tidak tersedia sebagaimana pemeriksaan sebelumnya. |

Bukti akhir berada pada C:/Users/rubik/.codex/artifacts/pace-green-20261104: home-live.png, home-desktop.png, home-mobile.png, cases-desktop.png, consult-desktop.png, login-desktop.png dan dashboard-desktop.png. Patokan dan screenshot final dibandingkan langsung melalui view_image. Server pratinjau, SQLite dan helper dibersihkan setelah verifikasi. Tidak mengubah layanan Apache/MySQL, konfigurasi sesi utama atau data produksi.
