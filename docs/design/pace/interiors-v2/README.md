# PACE — formulir layanan, November 2026

Empat konsep ImageGen menjadi acuan halaman biaya, status perkara, konsultasi, dan pengaduan. Implementasi memakai Blade dan kontrol HTML asli agar alur layanan yang ada tetap berfungsi.

## Sistem visual

- Kanvas putih `#ffffff`, teks `#183328`, hijau `#166534` / `#15803d`, panel sage `#f0f7f0`, emas `#d6a526`, panel bantuan biaya `#fcf7e8`.
- Judul Plus Jakarta Sans 46px (ponsel 29px), isi Inter 16px, keterangan 13px.
- Konten maksimum 1320px termasuk gutter 24px, kolom formulir dan panduan dengan jarak 24px. Pada layar kecil kolom menjadi satu.
- Panel radius 22px, padding 30px, garis sage tipis. Input minimal 48px, radius 10px. Garis pendek emas di bawah judul.
- Ilustrasi raster PACE yang sudah ada, tanpa overlay: kalkulator, berkas perkara, konsultasi, dan privasi. Teks, ikon, dan kontrol dibuat dalam HTML/CSS.
- Biaya: pihak berdampingan, hasil di samping. Status: pencarian tunggal dan penjelasan hasil. Konsultasi: tiga kartu radio asli, jadwal kondisional, domisili dalam details. Pengaduan: kelompok laporan, kejadian, dan bukti dengan pemilih berkas asli.
- Navigasi: Cari pengadilan, Konsultasi PTSP, Status perkara, Perkiraan biaya, Pengaduan.

Setiap `*-concept.png` memiliki `*-prompt.txt` sebagai brief. Tidak ada data atau nominal contoh yang dimasukkan ke formulir operasional.

Perbedaan yang diperlukan: logo PACE memakai aset resmi aplikasi, bukan bentuk logo rekaan konsep. Bantuan wajib/opsional, persetujuan, sumber tarif, serta petunjuk pemulihan lampiran tetap mengikuti aturan produk. Tampilan hasil dihitung server. Pilihan berkas memakai tampilan asli browser/OS. Form panjang tetap menggulir pada dokumen dan tidak dipaksa masuk satu viewport.
