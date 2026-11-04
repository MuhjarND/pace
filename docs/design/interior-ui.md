# Patokan halaman lanjutan · 3 November 2026

Pengguna meminta seluruh halaman lainnya mengikuti UI beranda terbaru. Tiga konsep dibuat dengan Image Gen bawaan sebelum implementasi; PNG hanya menjadi referensi, bukan pengganti HTML atau data aplikasi.

## Referensi dan brief

| Referensi | Brief final dan aturan penerapan |
|---|---|
| [Interior publik](public-interior-reference.png) | Layar lengkap cek perkara: header portal, pita judul peach dengan ikon dokumen, lima kanal ringkas, panel form putih 60–65%, panduan kuning 35–40%, bantuan hitam dan footer. Isian pengadilan dan nomor perkara asli, tombol Cek status. Tidak membuat hasil atau status rekaan. |
| [Ruang kerja](workspace-reference.png) | Sidebar putih 252px, landmark oranye, navigasi aktif peach, topbar pencarian/notifikasi, judul peach, pita satker hitam, empat ringkasan nyata dalam rel, daftar empat kanal, agenda konsultasi dan layanan. Tidak menambah chart, menu, atau analitik. Tanggal/nilai contoh pada PNG diganti data server. |
| [Akses akun](access-reference.png) | Komposisi dua kolom: panel brand peach dan form putih, judul Pelayanan dekat. Meski dari jauh., tiga kanal, label Email/Kata sandi, reveal, Ingat saya, Masuk, Konsultasi tanpa akun, Kembali ke beranda. Tidak menambah kredensial contoh pada produk. |

Semua brief mengunci putih murni, oranye/kuning/hitam dari token app.css, Plus Jakarta Sans/Inter, kontrol 46–54px, panel 20px, bayangan netral lembut, dokumen natural-height dan layout ponsel satu kolom. Tanpa gradasi, sidebar gelap, metrik rekaan, badge promosi atau perubahan framework. Aset ikon memakai Font Awesome yang sudah tersedia. Header/footer tetap mengikuti patokan beranda; variasi yang diciptakan generator pada logo, footer, separator atau janji penggunaan data tidak diadopsi. Salinan kebijakan, format nomor perkara, kondisi akses dan urutan navigasi aplikasi tetap otoritatif.

## Pemilik komponen

`app.css` tetap sumber token dan primitive. `public-portal.css` memiliki geometri beranda. `product-ui.css`, dimuat sesudah gaya halaman, memiliki varian visual lintas halaman; tidak mengubah warna token atau perilaku JavaScript. Shell publik menandai citizen-home/citizen-interior. Shell petugas menggunakan workspace-page. Login/register menggunakan auth-page, pemulihan/verifikasi menggunakan auth-recovery, dan identifikasi lama menggunakan public-access.

`public/partials/page-heading.blade.php` memiliki judul/ikon tugas; `guide-steps.blade.php` memiliki panduan bernomor. Nomor dekoratif disembunyikan dari pembaca layar. `service-links.blade.php` tetap sumber label/rute publik; `partials/page-heading.blade.php` tetap sumber heading petugas. `ui.js`, `app.js`, controller dan policy tetap pemilik perilaku.

## Cakupan penerapan

- Publik: biaya, perkara/akta dan hasilnya, konsultasi, tiket/undangan, pengaduan, pelacakan dan detail laporan.
- Ruang kerja: dashboard, katalog, kotak masuk/detail chat, voice, Google Meet/tautan/antrean, call center, antrean operasional, jam layanan, wilayah/tarif, akun/satker dan formulirnya, kotak masuk/detail pengaduan PTA.
- Akses: login, registrasi, identifikasi nomor HP, formulir pelengkap, pemulihan dan verifikasi akun. Tautan masyarakat pada login menuju konsultasi tamu yang sudah tersedia.

Ponsel: judul tanpa ikon dekoratif besar, panel form dan panduan bertumpuk, kartu layanan ringkas, menu petugas berupa drawer, tabel menggulir internal. Wrapper tabel position:relative menjaga label sr-only tetap dalam clipping tabel; tidak menyembunyikan overflow seluruh halaman sebagai penyelesaian.

## Batas data dan QA

Database MariaDB utama berhenti saat membaca work_units. Log 3 November mencatat kegagalan baca halaman InnoDB (read 0 byte) lalu crash; tidak dilakukan restore, perubahan skema, atau penulisan data QA ke database utama. Review browser memakai SQLite sementara di port 8017 dengan Fonnte dinonaktifkan, akun dan laporan yang jelas berlabel uji, serta snapshot wilayah lokal. Ini membuktikan render/interaksi UI, bukan kesehatan database utama atau integrasi eksternal. Detail hasil verifikasi terkini ada di docs/UI_REVIEW.md.
