# Data dummy layanan PTSP

Katalog contoh untuk pengembangan berisi 20 layanan aktif, masing-masing dengan nama, deskripsi, kategori, dan ikon Font Awesome:

| Kategori | Jumlah | Contoh |
| --- | --- | --- |
| Perkara | 7 | Cerai gugat, cerai talak, isbat nikah, e-Court |
| Persidangan | 3 | Jadwal sidang, mediasi, persidangan elektronik |
| Kepaniteraan | 4 | Salinan putusan, akta cerai, legalisasi, sisa panjar |
| Informasi | 4 | Informasi PTSP, biaya perkara, Posbakum, informasi publik |
| Pengaduan | 2 | Penyampaian dan tindak lanjut pengaduan |

Data berada pada `database/seeds/ServiceSeeder.php`. Untuk mengisi katalog saja:

```powershell
composer dump-autoload --no-scripts
php artisan db:seed --class=ServiceSeeder --force
```

Seeder mencocokkan nama layanan, hanya menambahkan yang belum ada, dan dapat dijalankan ulang tanpa menggandakan data. Nama, deskripsi, ikon, kategori, dan status layanan yang sudah ada tidak ditimpa. Data disimpan dalam satu transaksi.

Perintah khusus di atas tidak membuat akun, chat, meeting, atau log panggilan. `DatabaseSeeder` umum juga memakai katalog ini, tetapi tetap memiliki akun dan aktivitas demo tersendiri; menjalankan seeder umum dapat memperbarui password akun masyarakat demo.

Layanan tampil di halaman `/services`, pilihan layanan saat membuat chat, dan katalog dashboard. Deskripsi merupakan contoh isi untuk pengembangan; sesuaikan dengan layanan yang benar-benar disediakan tiap satker sebelum digunakan sebagai informasi resmi.

## Akun dummy admin dan petugas

`StaffSeeder` menyediakan satu admin PTA Papua Barat, satu operator PTA dengan hak monitoring, dan empat operator satker (Sorong, Manokwari, Kaimana, Fak-Fak). Seeder ini khusus lingkungan `local` atau `testing`.

```powershell
php artisan db:seed --class=StaffSeeder --force
```

Akun yang sudah ada berdasarkan email dipertahankan, termasuk password dan perannya. Akun baru mendapat password acak yang berbeda, disimpan sebagai hash di database. Salinan kredensial untuk pengujian lokal tersimpan di `storage/app/private/dummy-staff-credentials.json`; direktori ini diabaikan Git dan dilindungi dari akses HTTP Apache dengan `.htaccess`. Jangan menyalin berkas kredensial ke direktori publik.

Masuk melalui `/staff-login` menggunakan email dan password akun. Akun demo tidak diberi nomor telepon; isi kontak nyata ketika menyiapkan petugas sungguhan. Status online awal adalah tidak aktif. Admin memiliki hak mengelola layanan dan memantau semua satker; operator PTA dapat memantau semua satker, sedangkan operator PA mengikuti cakupan satkernya.

Pada 27 Oktober 2026, enam akun staf sudah dibuat dan diuji melalui login HTTP lokal beserta tampilan hak monitoring dan pengelolaan layanan. Seeder yang diulang mempertahankan akun serta hash password. Satu akun masyarakat yang sudah ada dan 20 layanan tetap utuh. Akses HTTP ke berkas kredensial mengembalikan 403.
