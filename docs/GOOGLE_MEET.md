# Konsultasi Google Meet dan Antrean PTSP

Pembaruan 27 Oktober 2026: petugas cukup menyimpan tautan ruang Google Meet tetap untuk satkernya. Pengguna menentukan jadwal sendiri; tidak perlu menunggu petugas menerbitkan slot.

## Petugas

1. Buka Google Meet → **Tautan Google Meet Satker** (`/meetings/manage`), lalu simpan tautan ruang yang telah disiapkan untuk penggunaan sepanjang tahun. Aplikasi memakai ulang tautan tanpa menetapkan kedaluwarsa. PA Sorong sudah menggunakan `https://meet.google.com/hrg-wbdh-xvg`.
2. Buka daftar jadwal dan antrean. Petugas melihat pemohon, topik, jadwal WIT, nomor antrean, verifikasi, dan status pelayanan untuk satkernya.
3. Jadwal mendatang tetap dapat diverifikasi/ditolak. Pemantau pusat dapat membaca satker lain tetapi tidak dapat melayani atau mengubah antrean satker lain.
4. Layani sesuai urutan. Tombol **Mulai Layani** dan **Tandai Selesai** hanya berlaku untuk giliran terdepan. Menyelesaikan/membatalkan antrean membuat pengguna berikutnya maju.

Perubahan tautan memperbarui konsultasi aktif di satker yang sama. Keberlakuan ruang di layanan Google tetap mengikuti pengaturan penyelenggara; aplikasi tidak membuat atau memperpanjang ruang melalui API.

## Pengguna

1. Halaman pengajuan menampilkan antrean aktif satker secara anonim: nomor, jadwal, dan status. Nama serta topik pengguna lain tidak ditampilkan.
2. Pilih tanggal, waktu, dan topik. Tanggal tersedia hingga 365 hari ke depan; waktu berupa pilihan setiap 15 menit dalam WIT, dengan pilihan **Sekarang** untuk hari ini. Pengguna tidak memasukkan tautan atau durasi.
3. Jadwal mendatang masuk daftar konsultasi dan menunggu verifikasi. Beberapa pengguna dapat memilih waktu yang sama dan akan dilayani berurutan. Permohonan mendatang baru dapat masuk antrean aktif setelah waktunya tiba dan sudah diverifikasi.
4. Untuk **Sekarang**, antrean kosong langsung mengarahkan pengguna ke Google Meet. Jika ada antrean, pengguna diarahkan ke halaman bergabung PTSP dengan nomor dan keterangan **Menunggu giliran**.
5. Halaman antrean menyediakan tombol **Buka Google Meet**. Pengguna boleh membuka ruang sambil menunggu, tetapi tindakannya tidak mengubah status menjadi sedang dilayani jika masih ada orang di depannya.
6. Halaman PTSP memperbarui antrean setiap 5 detik selama terbuka. Setelah antrean sebelumnya selesai, judul berubah menjadi **Giliran Anda sudah tiba**. Penerimaan dan pelayanan di ruang Google Meet dilakukan oleh petugas.

Keterangan antrean berada pada halaman PTSP sebelum membuka Google Meet. Tidak ada modifikasi antarmuka Google Meet atau kontrol penerimaan peserta melalui API.

## Urutan dan konsistensi

Nomor `A001`, `A002`, dan seterusnya diberikan per satker per tanggal WIT. Nomor permohonan yang dibatalkan tidak digunakan ulang. Antrean aktif mengutamakan pengguna yang sedang dilayani, kemudian waktu konsultasi yang telah tiba dan ID permohonan. Dengan demikian, jadwal lama yang baru diverifikasi tidak menggusur sesi yang sedang berjalan.

Pengiriman ganda dari akun yang sama untuk waktu yang sama menggunakan permohonan aktif yang sudah ada. Transaksi dengan penguncian pengaturan satker serta indeks unik nomor antrean mencegah duplikasi. Pengguna tidak dapat memilih satker lain melalui manipulasi formulir.

Antrean tidak kedaluwarsa hanya karena estimasi durasi terlewati; petugas menyelesaikan atau membatalkan konsultasi secara eksplisit. Field durasi merupakan estimasi 30 menit, bukan batas waktu otomatis. Nilai horizon pemesanan dan estimasi ada di `config/ptsp.php`.

## Data dan migrasi

Migrasi `2026_10_27_000010_add_consultation_queue` menambahkan nomor dan tanggal antrean serta mengisi riwayat yang sudah ada. Data slot dan relasi historis tetap disimpan untuk kompatibilitas, tetapi tidak lagi menjadi syarat pemesanan atau dikelola melalui UI. Tidak ada penghapusan tabel/riwayat.

Cadangan sebelum migrasi disimpan di `storage/app/private/queue-before-*.json`. Direktori privat diabaikan Git dan diblokir dari HTTP Apache. Tanggal input/tampilan memakai `Asia/Jayapura`; datetime disimpan dalam UTC.

## Desain dan verifikasi

Tampilan menggunakan layout, `glass-card`, tombol, badge, ikon Font Awesome, serta variabel warna/radius yang sudah ada. Grid berubah menjadi satu kolom pada layar sempit. Pengaturan tautan, formulir pengguna, dan halaman antrean telah diperiksa secara visual di browser.

Seluruh 34 tes proyek lulus dengan 224 assertion. Uji HTTP lokal memeriksa penjadwalan mandiri, daftar petugas, verifikasi, antrean kosong, antrean menunggu, pengalihan Google Meet tanpa mengambil giliran, serta majunya antrean setelah selesai. Perubahan judul giliran otomatis juga terverifikasi di browser. Akun dan permohonan sementara pengujian sudah dihapus. Ruang eksternal Google tidak dibuka untuk pengujian panggilan.

## Voice to Chat

Petugas dapat memilih percakapan masyarakat sesatker lalu mendiktekan atau mengetik balasan. Masyarakat tetap terhubung ke PTSP satkernya. Dikte memerlukan browser yang mendukung Web Speech API dan izin mikrofon; input teks tetap tersedia.
