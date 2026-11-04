# PACE PTA Papua Barat

Patokan aktif header, footer dan identitas aplikasi, 4 November 2026. Permintaan pengguna: desain ulang header, hilangkan keterangan Papua Barat & Papua Barat Daya pada footer, buat logo baru, gunakan nama PACE PTA Papua Barat.

## Visual

`header-reference.png` dibuat dengan Built-in Image Gen sebelum implementasi. Brief lengkap ada di `prompts.json`. Simbol baru memadukan huruf P dengan percakapan, oranye dan aksen kuning. Bukan lambang resmi pengadilan. Wordmark PACE dan subjudul PTA Papua Barat berupa teks HTML agar tajam dan terbaca teknologi bantu. Tidak membuat kepanjangan akronim PACE.

Header putih desktop: tinggi sekitar 90px, maksimum 1280px termasuk padding 24px, simbol 48px, nama 29px, navigasi teks dengan garis aktif oranye, satu tombol Login petugas hitam. Inter untuk kontrol, Plus Jakarta Sans untuk identitas. Footer hanya menampilkan logo dan identitas aplikasi, tinggi sekitar 80px; tidak menampilkan baris cakupan wilayah. Palet tetap mengikuti token `app.css`.

Pada <=1050px navigasi menjadi disclosure inline, bukan dialog: simbol 38–42px, tombol login dan menu tetap terlihat, tautan 48px tinggi. Escape mengembalikan fokus ke tombol menu; klik luar menutup tanpa memindahkan fokus. Perubahan breakpoint menormalkan keadaan dan memulihkan fokus jika kontrol yang sedang digunakan menjadi tersembunyi. Tanpa JavaScript semua tautan tetap terlihat. Pada <360px tombol login memakai ikon dengan nama aksesibel tetap Login petugas. Ini penyesuaian ukuran konkret agar kontrol tetap muat, sesuai susunan mobile pada konsep.

## Pemilik implementasi

- `config/ui.php`: satu sumber nama, institusi dan nama berkas logo.
- `resources/views/partials/brand.blade.php`: lockup logo/wordmark bersama.
- `public/css/pace-brand.css`: identitas/header/footer dan varian sidebar/akun.
- `public/js/navigation.js`: disclosure navigasi publik; drawer petugas tetap milik app.js.
- `resources/views/public/partials/service-links.blade.php`: daftar route dan keadaan menu aktif tetap bersama. Label visual header perkara dipendekkan; nama aksesibel lengkap dipertahankan.

## Aset produksi

- `public/images/pace-logo-source.png`: master transparan hasil Built-in Image Gen.
- `public/images/pace-mark.webp`: 192px untuk tampilan logo, alpha dipertahankan.
- `public/images/pace-favicon.png`: 64px.
- `public/images/pace-touch-icon.png`: 180px.

Pengubahan ukuran memakai GD, tanpa menggambar ulang atau mengubah isi gambar. Nama ditampilkan konsisten pada shell publik, sidebar ruang kerja, login, pendaftaran, akses tamu dan judul tab. Konfigurasi APP_NAME/cookie lama tidak diganti agar sesi pengguna tidak terputus akibat perubahan visual. Nama layanan PTSP dan nama resmi satker dipertahankan.

## Verifikasi

115 tes / 946 asersi lulus. Pemeriksaan Blade dan sintaks config/JavaScript lulus. Browser memakai SQLite terisolasi, Fonnte dimatikan, karena MariaDB utama bermasalah pada pekerjaan sebelumnya. Hasil ini memverifikasi UI, bukan pemulihan database utama. Bukti terbaru dan pemeriksaan keyboard/ukuran layar dicatat di docs/UI_REVIEW.md.

## Penyesuaian berikutnya: header fixed dan satu navigasi

Sesuai permintaan pengguna, rel lima kartu navigasi di bawah judul halaman perkara, biaya dan pengaduan dihapus dari markup. Header tetap menjadi navigasi utama; galeri layanan bergambar di beranda dipertahankan. Header fixed setelah progressive enhancement navigation.js aktif. ResizeObserver menyamakan spacer dengan tinggi aktual header termasuk menu mobile dan perubahan ukuran font; fallback tanpa JavaScript tetap dalam aliran dokumen. Tinggi maksimum viewport dengan scroll internal menjaga menu tetap dapat diakses. Scroll-margin pada tujuan dalam main memberi ruang untuk header. Navigasi mobile dan Escape tetap menggunakan pemilik lama.

## Penggantian palet yang disetujui

Pengguna kemudian memilih konsep E hijau–emas beserta susunannya. Acuan aktif warna/logo/form/galeri sekarang ../green-gold/README.md. Catatan oranye pada dokumen ini menjadi riwayat; header fixed, disclosure dan identitas teks tetap memakai pemilik yang sama.
