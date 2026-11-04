# Tema PTSP Online

Palet terbaru mengikuti gambar pengguna: Bold Orange #FF4F00, Golden Yellow #FFC400, putih #FFFFFF, dan Deep Black #1C1C1C. Sumber token runtime tetap public/css/app.css; acuan lengkap ada di DESIGN.md dan UX-CONTRACT.md.

| Token | Nilai | Penggunaan |
| --- | --- | --- |
| --primary | #FF4F00 | Tombol utama, ikon tindakan |
| --yellow | #FFC400 | Menu/tab aktif, penanda operasional |
| --secondary / --text / --on-primary | #1C1C1C | Judul, isi, teks pada tombol oranye/kuning |
| --page-bg / --surface | #FFFFFF | Halaman, kartu, sidebar, header |
| --surface-muted | #F7F7F7 | Bidang kontrol dan header tabel |
| --text-muted | #626262 | Keterangan |
| --orange-ink | #A33100 | Teks aksen oranye yang terbaca di permukaan terang |
| --glass-border | #E6E6E6 | Batas panel |

Teks hitam terhadap oranye memiliki kontras 5,17:1; terhadap kuning 10,67:1. Tinta oranye pada putih mencapai 7,01:1. Hijau/merah tetap hanya untuk status sukses/kesalahan. Tema tetap terang, menggunakan tipografi Inter dan Plus Jakarta Sans, panel radius 20px, serta kontrol 12px. Tidak menjalankan Laravel Mix yang dapat menimpa stylesheet runtime.

Layar antrean menampilkan konteks satker, empat ringkasan aktual, tab antrean/verifikasi/riwayat, dan panel jam layanan. Form akun/satker serta pengaturan jam memakai kontrol shared yang sama. Tabel memiliki scroll horizontal lokal pada layar sempit; form panjang mengikuti scroll dokumen. Jam, kuota, status, dan angka berasal dari server. Pembaruan otomatis menjaga data terakhir jika gagal dan tidak mengganti kontrol yang sedang digunakan.
