# PTSP dengan ilustrasi · 4 November 2026

Acuan aktif untuk redesign website bergambar berdasarkan permintaan pengguna. Identitas tetap putih, oranye, kuning, dan hitam. Teks singkat dan fungsi layanan tetap menjadi prioritas. Konsep dan aset dibuat dengan tool Image Gen bawaan; brief lengkap disimpan di [prompts.json](prompts.json).

## Referensi visual

- [Beranda](home-reference.png): hero terbuka dua kolom, judul “Layanan pengadilan, dari mana saja.”, satu kalimat, dua aksi, ilustrasi pelayanan daring. Form wilayah melintang di bawahnya; empat pintasan bergambar; footer sederhana. Tidak menambah statistik, panduan panjang, atau fitur baru.
- [Konsultasi](consult-reference.png): judul dengan ilustrasi pendukung, satu form, persetujuan dan tombol, Petunjuk konsultasi tertutup awal. Tetap tanpa panel panduan samping atau rel navigasi kedua.

Header/footer memakai komponen aplikasi yang sudah ada agar kedua konsep konsisten. Label dropdown tetap terlihat di atas pilihan untuk mempertahankan konteks setelah dipilih; tidak hanya mengandalkan placeholder di dalam kontrol. Form konsultasi dibatasi 900px dan gambar 205×130px agar tetap ringkas, mengikuti brief. Varian glyph dan reveal mengikuti Font Awesome/ui.js. Tidak mengadopsi data, badge, tanggal atau elemen tambahan hasil generator. Gambar bukan screenshot pengganti HTML.

## Aset produksi

| Aset di public/images | Dimensi | Byte | Fungsi |
|---|---:|---:|---|
| ptsp-service-hero.webp | 1400×933 | 114610 | Pelayanan daring, pengadilan generik, pin/peta; beranda dan login |
| ptsp-consultation.webp | 800×533 | 41676 | Petugas di laptop, bubble percakapan dan pengadilan; konsultasi/dashboard |
| ptsp-fees.webp | 800×533 | 36428 | Kalkulator, koin dan lembar biaya |
| ptsp-case-files.webp | 800×533 | 33650 | Folder, dokumen dan simbol perkembangan perkara |
| ptsp-privacy.webp | 800×533 | 31510 | Amplop tertutup dan perisai; pengaduan/pelacakan |

Seluruh aset mempunyai alpha transparan (sudut alpha 127 pada GD). Konversi PNG ke WebP kualitas 86 mempertahankan alpha, hanya mengubah format/dimensi. Tidak ada tint, filter, overlay atau crop pada CSS. Hero diedit dengan Image Gen untuk menghapus aksesori kepala yang tidak diminta dan menampilkan rambut alami serta komposisi lengkap. Gambar bersifat ilustratif: bukan foto petugas, bangunan satker atau peta wilayah nyata; simbol centang bukan hasil status kasus tertentu. Tidak memuat nama, nomor perkara, identitas, logo resmi atau data pribadi.

## Sistem dan pemilik

`app.css` tetap sumber token runtime. `illustrated-portal.css` memiliki showcase, galeri gambar dan varian heading bergambar; dimuat setelah `public-portal.css`/`product-ui.css`. Tidak ada token warna baru. Plus Jakarta Sans/Inter, kontrol 46–50px dan panel radius 18–20px mengikuti sistem yang sama.

`config/ui.php` mencatat nama dan ukuran aset. `public/partials/illustration.blade.php` menjadi pemilik img dengan ukuran eksplisit, alt kosong/aria-hidden untuk gambar dekoratif, lazy loading dan varian priority. `service-links.blade.php` tetap sumber label, tautan, konteks region/unit dan menu aktif; varian withIllustrations menampilkan empat layanan setelah finder dikecualikan karena form pencarian sudah ada di atas.

Di ponsel, hero menjadi judul/aksi → ilustrasi 200px → form. Tombol Cari pengadilan langsung menuju dan memfokuskan form, sehingga pengguna dapat melewati gambar. Empat layanan menjadi dua kolom. Heading formulir memakai gambar kecil 76–80px; gambar dashboard disembunyikan pada ponsel. Form panjang tetap scroll dokumen dan popup tetap native. Seluruh gambar object-fit:contain dengan ruang yang dipesan; tidak memotong isi gambar atau menggeser kontrol saat dimuat.

## Ruang lingkup dan batas

Beranda/pencarian, biaya, perkara/akta, konsultasi, pengaduan/pelacakan, login, dan sambutan dashboard memakai gambar baru. Tabel operasional dan percakapan mempertahankan kepadatan kerjanya. Tidak mengubah endpoint, aturan wilayah/tarif, izin, anonimitas, status antrean atau integrasi Fonnte/Kasuari.

Pengujian memakai SQLite sementara dan snapshot wilayah lokal karena kendala MariaDB utama yang telah dicatat sebelumnya. Tidak melakukan restore atau migrasi pada database utama. Seluruh data dummy QA hanya berada di lingkungan uji; tidak mengirim laporan, konsultasi, WhatsApp atau membuka Meet eksternal. Hasil pemeriksaan aktual dicatat di docs/UI_REVIEW.md.
