---
version: alpha
name: PACE PTA Papua Barat
description: Meja layanan digital pengadilan dengan dasar putih dan penanda kanal berwarna.
colors:
  primary: "#15803D"
  secondary: "#183328"
  yellow: "#D6A526"
  on-primary: "#FFFFFF"
  orange-ink: "#166534"
  success: "#047857"
  orange: "#15803D"
  rose: "#183328"
  background: "#FFFFFF"
  surface: "#ffffff"
  text: "#183328"
  muted: "#626262"
  border: "#E6E6E6"
typography:
  display:
    fontFamily: '"Plus Jakarta Sans", "Inter", system-ui, sans-serif'
  body:
    fontFamily: '"Inter", system-ui, sans-serif'
rounded:
  DEFAULT: "12px"
  lg: "20px"
spacing:
  page: "32px"
  gap: "24px"
components:
  button:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
  field:
    textColor: "{colors.text}"
  divider:
    backgroundColor: "{colors.border}"
  caption:
    textColor: "{colors.muted}"
  success:
    textColor: "{colors.success}"
  information:
    textColor: "{colors.orange-ink}"
  complaint:
    textColor: "{colors.rose}"
  satker-mark:
    backgroundColor: "{colors.orange}"
    textColor: "{colors.on-primary}"
  sidebar:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
  guide-number:
    backgroundColor: "{colors.yellow}"
    textColor: "{colors.text}"
---

# PACE PTA Papua Barat

## Overview

Acuan aktif yang disetujui pengguna: **konsep E hijau–emas**, docs/design/green-gold/home-reference.png dan README.md. Beranda menggunakan susunan konsep B/E: judul dan ilustrasi masyarakat/petugas di kiri, form empat dropdown vertikal di kanan, empat pintasan bergambar di bawah, dan footer identitas terpusat. Desain/palet oranye sebelumnya menjadi riwayat.

PACE membantu masyarakat menemukan pengadilan, memperkirakan biaya, memeriksa perkara dan akta cerai, berkonsultasi tanpa akun, dan menyampaikan pengaduan anonim. Publik memakai register informasi singkat; ruang petugas memakai register produk dengan tabel/form operasional. Bahasa Indonesia, WIT Asia/Jayapura, kalender Gregorian. Data, status dan hak akses berasal dari server, tanpa angka, tarif atau klaim layanan rekaan.

## Colors

public/css/app.css adalah sumber runtime kanonis (Model B). --primary #15803D / RGB 21,128,61; hover --primary-dark dan --brand-ink #166534; --secondary dan --text #183328; --gold #D6A526 / RGB 214,165,38. --on-primary putih #FFFFFF memiliki kontras 5,02:1 pada hijau; teks #183328 pada emas 6,01:1. Emas untuk aksen/panduan, bukan teks kecil pada putih. Kanvas dan panel utama putih. --surface-brand #F0F7F0 untuk pilihan/panel lembut; --surface-guide #FCF7E8 dan --border-guide #E7D6A4 untuk panduan. Muted #626262 dan border #E6E6E6 tetap netral.

Alias lama --orange, --orange-rgb, --orange-ink dan --surface-orange mengacu ke hijau/permukaan brand; --yellow/--yellow-rgb mengacu ke emas agar komponen lama ikut konsisten tanpa implementasi tema ganda. Hijau sukses, merah error dan warning tetap mempunyai label/ikon semantik. Logo hijau dan ilustrasi hijau–emas melalui config/ui.php, bukan filter/tint CSS pada gambar lama.

## Typography

Plus Jakarta Sans untuk judul/identitas, Inter untuk isi dan kontrol, fallback system-ui. Beranda desktop memakai judul besar dua baris sekitar 74px pada 1487px, isi 22px, judul form 36px, label/field 16px, tombol 22px. Ponsel: judul 36px, isi 15px dan field 16px. Halaman produk memakai hierarki lebih tenang dengan judul 27–42px dan kontrol 14–16px. Nama satker/isi panjang dapat membungkus; angka operasional tabular.

## Layout

Header publik fixed dengan navigasi lima kanal, tombol Login petugas hijau, spacer tinggi aktual dan offset anchor milik navigation.js. Pada <=1050px navigasi menjadi disclosure inline yang tetap dapat digulir pada viewport pendek. Beranda maksimum 1440px: kiri 1,35 bagian dan kanan 1 bagian, gap 28px, form berdiri sendiri dengan radius 28px. Galeri empat layanan memakai satu baris panel putih bergambar di kiri label. Tidak ada rel kartu navigasi kedua di interior.

Pada <=900px aliran beranda menjadi judul → ilustrasi → form → layanan, semuanya satu dokumen dengan galeri dua kolom. Tidak ada penguncian tinggi halaman. Interior mempertahankan form/hasil dan panduan, konsultasi satu form terpusat dengan petunjuk details native. Ruang kerja mempertahankan sidebar putih 252px dan header 76px, padding 28px, tabel dengan overflow horizontal sendiri. Ponsel sidebar menjadi drawer milik app.js.

## Elevation & Depth

Panel putih dengan garis netral, --shadow-public-panel lembut pada form/panel utama. Permukaan sage dan emas pucat menandai konteks dan panduan. Tidak memakai dark mode atau gradasi dekoratif pada kontrol. Gambar transparan dengan material alami, tanpa overlay/tint. Footer hanya logo, PACE dan PTA Papua Barat, tanpa keterangan wilayah.

## Shapes

Kontrol radius 10–12px, panel utama 20–28px. Galeri beranda berupa empat pintasan fungsi yang sudah ada, bukan penambahan fitur baru. Ikon navigasi tetap satu keluarga Font Awesome; gambar 3D hanya memperjelas tugas.

## Components

Token app.css → tombol, field, badge, status, navigasi dan shell. Struktur portal public-portal.css, varian interior/petugas/akun product-ui.css, showcase/galeri/ilustrasi illustrated-portal.css, identitas/header/footer pace-brand.css. config/ui.php → partials/brand dan public/partials/illustration → aset hijau–emas dengan dimensi eksplisit. Jangan menjalankan Laravel Mix yang menimpa public/css/app.css.

ui.js tetap memiliki validasi/error inline, busy submit, password/ticket reveal, clear search, dialog dan live status; public-portal.js memiliki dropdown wilayah; app.js memiliki sidebar; navigation.js memiliki menu publik/fixed-header spacer. Native select/date tetap kanonis. Hover/focus tidak menggeser kontrol; reduced-motion menghormati preferensi OS. Scrollbar dan forced-colors mengikuti owner bersama.

## Do's and Don'ts

- Pertahankan satker, scope, antrean, sumber wilayah/perkara, status server dan anonimitas.
- Gunakan salinan singkat, label terlihat, tujuan aksi yang jelas, serta error yang dapat dipulihkan.
- Jangan membuat angka, harga, status online atau tenggat layanan rekaan.
- Jangan mengembalikan rel kartu kanal yang telah dihapus pada halaman interior.
- Jangan memakai gambar screenshot sebagai UI atau mengganti native input dengan elemen dekoratif.

## Operations and account management

Admin pusat (admin dengan can_monitor) mengelola akun dan daftar satker dinamis. Petugas mengatur jam dan hari tutup satkernya sendiri; pemantau hanya membaca antrean satker lain. Jam tidak diterapkan sampai disimpan dan diaktifkan secara eksplisit. Kuota harian menghitung seluruh pengajuan yang tidak dibatalkan, termasuk yang menunggu verifikasi dan sudah selesai. Riwayat tidak dibatalkan saat jam diubah. Nama satker tetap setelah dibuat karena merupakan identitas pada riwayat; kode internal, alamat, telepon, dan penerimaan layanan baru dapat diperbarui.

Tabel akun/satker/antrean memakai pagination server dan wrapper scroll horizontal. Filter akun serta tampilan antrean disimpan di query string. Antrean diperbarui tiap 10 detik saat tab terlihat; polling mempertahankan data terakhir saat gagal dan menunda penggantian kontrol yang sedang digunakan. Server memakai WIT untuk tanggal lokal, UTC untuk waktu database. Endpoint ketersediaan hanya mengembalikan jadwal satker pengguna; endpoint antrean hanya untuk petugas/pemantau yang berhak. Sumber implementasi dan batasan perilaku ada di docs/OPERASIONAL_PTSP.md dan UX-CONTRACT.md.


## Historical visual notes (superseded by green-gold concept E)

Catatan visual bertanggal di bawah adalah riwayat. Acuan aktif adalah konsep E hijau–emas dan bagian sebelumnya; alur/izin tetap berlaku.

### Citizen portal (November 2026)

**Acuan aktif setelah permintaan website bergambar:** `docs/design/illustrated/home-reference.png` dan `consult-reference.png`, dengan spesifikasi di README direktori yang sama. Hero judul/ilustrasi, form melintang, empat layanan bergambar. Pada ponsel ilustrasi 200px mendahului form dan dapat dilewati lewat CTA Cari pengadilan. Gambar figur/bangunan/peta generik tidak mewakili identitas nyata. Kelima kanal tetap tersedia pada header. Palet, font, otorisasi dan kontrol kanonis tetap berlaku. Catatan revisi sebelumnya di bawah adalah riwayat, bukan acuan layout aktif.

Pembaruan 2 November 2026 menambahkan kedalaman yang terukur: hero dua kolom dengan judul dua warna dan satu ilustrasi lokasi, rel lima kanal berikon, panel tugas berelevasi, petunjuk bernomor dan pita bantuan kontras. Konsep acuan terbaru: `C:/Users/rubik/.codex/generated_images/01a17bcb-2b86-7d31-a701-3b502c5b0060/exec-6da3c42a-4683-48cb-b20b-3bc050982e3c.png`. Salinan UI dan kontrol tetap HTML/Blade, bukan gambar. Aset `public/images/court-location.webp` adalah simbol lokasi pengadilan generik, bukan bangunan satker tertentu atau peta geografis.

Token runtime tetap dimiliki public/css/app.css. Varian publik memakai --surface-orange #FFF3EB, --surface-guide #FFF8DB, --border-guide #F5DE8D, --shadow-public-panel (bayangan netral dua lapis), dan --shadow-public-rail (bayangan netral rel kanal). Ilustrasi transparan tanpa overlay/tint, dikonversi dari keluaran Image Gen menjadi WebP 107 KB; dimensi dicantumkan untuk stabilitas layout. Hero tidak memiliki badge/eyebrow atau angka rekaan. Teks tombol utama tetap hitam pada oranye mengikuti kontras kanonis, walaupun konsep menampilkan teks putih.

Header dan rel kanal berbagi `public/partials/service-links.blade.php` agar label, rute, ikon dan penanda halaman aktif tidak menyimpang. Di layar kecil header mengelola scroll navigasinya sendiri, rel kanal menjadi dua kolom, panel menjadi satu kolom, dan aksi utama tetap dapat langsung menuju form wilayah. Tidak ada perubahan alur, cakupan wilayah, izin, atau kebijakan anonimitas.

Perbandingan konsep dan implementasi melalui view_image pada 2 November 2026:

| Bagian | Pemeriksaan dan hasil |
|---|---|
| Komposisi | Hero dua kolom, rel lima kanal, form/petunjuk berdampingan dan pita bantuan dipertahankan; kontainer mengikuti lebar aplikasi responsif. |
| Warna | Kanvas putih, aksen oranye/kuning/hitam dan petunjuk kuning pucat mengikuti token; tidak ada overlay pada gambar. |
| Tipografi | Plus Jakarta Sans pada judul, Inter pada isi/kontrol; judul dua warna dan underline pendek dipertahankan. |
| Ikon | Landmark, lokasi, koin, dokumen, percakapan dan pengaduan memakai Font Awesome; dokumen/percakapan menggunakan varian regular agar mengikuti konsep. |
| Kedalaman | Bayangan panel/rel, ilustrasi transparan dan kontras pita bantuan membuat lapisan tugas jelas tanpa animasi panel informasi. |
| Kontrol/salinan | Dropdown native dan form tetap berfungsi; CTA, judul dan lima kanal berasal dari konsep serta fungsi yang sudah ada. Deskripsi pengaduan menyebut gratifikasi sesuai kebutuhan produk. Teks tombol utama hitam adalah deviasi kontras yang disengaja. |

Pemeriksaan menggunakan IAB/CUA pada desktop 1440×1100, ukuran konsep 1374×1145, dan ponsel 390×844. Hasil Aimas diarahkan ke PA Sorong melalui dropdown bertingkat; CTA memfokuskan form; popup native, validasi kosong serta navigasi ke biaya, konsultasi, perkara dan pengaduan diperiksa. Console tab QA tidak mencatat error/warning. PHPUnit 115 tes/946 assertion, syntax sembilan JavaScript, kompilasi Blade dan lint DESIGN.md lulus. Audit statis premium dicoba tetapi Python belum tersedia; audit ini tidak diklaim lulus. Tidak ada pengiriman laporan, konsultasi, atau WhatsApp pada pemeriksaan UI ini.

Ilustrasi dibuat memakai tool Image Gen bawaan. Brief aset: gedung pengadilan putih generik pada peta kertas terlipat, pin oranye, rute kuning, kamera isometrik, bayangan lembut, latar transparan, tanpa teks/logo/identitas satker. Referensi berasal dari konsep terbaru di atas; aset final berada pada `public/images/court-location.webp` (1536×1024, 107.014 byte).

Portal publik memakai shell horizontal dengan lima jalur tugas: Cari pengadilan, Perkiraan biaya, Cek status perkara dan akta cerai, Konsultasi PTSP, Pengaduan. Tetap memakai token oranye/kuning/putih/hitam yang sama, Inter dan Plus Jakarta Sans. Acuan awal portal: exec-e0f01c2e-1ef6-46b1-84f2-3ceef444a630.png. CSS varian public/css/public-portal.css memperluas token kanonis; panel petunjuk kuning pucat, formulir natural-height, native select bertingkat dengan panah native. Hasil pencarian memakai anchor agar terlihat sesudah submit. Halaman tiket tidak menampilkan data pemohon lain; data kosong tidak diisi statistik/nominal rekaan. Tidak ada perubahan identitas visual.

Pengaduan memperluas komponen yang sama melalui public/css/complaints.css: formulir anonim, receipt kode rahasia yang dimask, timeline tanggapan, serta kotak masuk PTA dengan ringkasan dan tabel metadata. Tidak ada identitas pelapor pada formulir atau detail PTA. Penanda catatan internal memakai panel netral berlabel jelas; warna status selalu disertai teks. Halaman privat tidak menyimpan konten melalui cache browser. Kebijakan akses dan anonimitas berasal dari docs/PENGADUAN.md.

## Konsultasi ringkas · 4 November 2026

Permintaan pengguna: sederhanakan tampilan dan hilangkan teks yang tidak diperlukan, pada konteks halaman konsultasi. Varian citizen-consult-page di product-ui.css memakai satu judul, satu form maksimal 820px, serta navigasi header yang sudah ada. Hero deskriptif, pengulangan lima kanal, judul form kedua dan panel panduan samping dihapus. Panduan pindah ke details/summary Petunjuk konsultasi, tertutup secara default. Label, petunjuk wajib/opsional WhatsApp, waktu WIT, konteks pemetaan yang belum terverifikasi, persetujuan dan daftar tiket tetap tersedia. Pengaduan tetap memiliki tautan tersendiri. Ini penyesuaian kecil dalam sistem visual yang sama, tanpa konsep Image Gen baru atau perubahan endpoint, izin dan alur pemesanan.

Header publik PACE kini fixed dengan spacer dinamis mengikuti tinggi aktual; tujuan anchor mempunyai offset header. Rel kartu navigasi tambahan di halaman interior dihapus sesuai permintaan pengguna. Header tetap navigasi utama dan galeri bergambar beranda tetap ada. Detail: docs/design/pace/README.md.
