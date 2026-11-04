# Pengaduan layanan dan gratifikasi

## Kebijakan dan alur

Sumber kebijakan: permintaan pengguna pada 2 November 2026. Laporan terkait setiap satker masuk ke PTA Papua Barat. Pembaca internal dibatasi kepada admin pusat PTA dan petugas pengaduan khusus yang ditunjuk. Pengguna kemudian menetapkan laporan anonim dan kerahasiaan data pelapor.

1. Masyarakat membuka `/pengaduan` tanpa login, memilih satker serta jenis laporan, lalu mengisi judul, tanggal kejadian dan kronologi. Lokasi, pihak terkait dan bukti bersifat opsional.
2. Formulir tidak meminta nama, email atau telepon. Server mengabaikan field identitas yang dikirim di luar formulir; service juga selalu menyimpan `is_anonymous=true` dengan nama/kontak kosong. Identitas akun yang sedang login tidak disalin dan tidak ada relasi pelapor ke akun pengguna.
3. Setelah tersimpan, pelapor menerima nomor laporan dan kode rahasia acak. Kode asli ditampilkan sekali melalui receipt; database hanya menyimpan hash SHA-256. Simpan kode sendiri: tidak ada pemulihan kode melalui identitas, email atau WhatsApp.
4. Tiket memperlihatkan laporan sendiri, bukti sendiri, status dan pesan pengelola. Pada perangkat lain, diperlukan nomor laporan **dan** kode melalui POST `/pengaduan/lacak`. Nomor saja tidak membuka laporan. Akses sesi berlaku dua jam; tombol **Tutup akses di perangkat ini** mencabut akses perangkat tanpa menghapus laporan.
5. Pengelola PTA membuka `/pta/pengaduan`, menyaring satker/jenis/status/nomor lengkap, membaca bukti, lalu mencatat status dan pesan untuk pelapor. Catatan internal dipisahkan dan tidak dimuat pada tampilan pelapor.

Seluruh satker terdaftar dapat dilaporkan, termasuk satker yang menonaktifkan penerimaan konsultasi. Kanal katalog berkategori `pengaduan` atau `gratifikasi` mengarah ke formulir PTA, termasuk jika endpoint pembukaan chat lama dipanggil langsung. Riwayat chat lama tidak dipindahkan.

Anonimitas berarti aplikasi tidak meminta atau menautkan identitas pelapor. Teks atau berkas yang diunggah dapat memuat identitas secara sukarela, termasuk metadata dokumen; aplikasi memberi petunjuk untuk menghindarinya dan tidak mengubah isi bukti. Jangan menjanjikan anonimitas jaringan atau bahwa isi berkas otomatis disamarkan.

## Penunjukan pengelola

Admin pusat yang aktif, nonguest, berperan `admin`, memiliki `can_monitor`, dan tepat berada pada **Pengadilan Tinggi Agama Papua Barat** dapat mengelola akses ini. Admin pusat PTA yang sudah ada otomatis memiliki akses pengaduan.

Untuk menunjuk petugas: **Akun pengguna → Ubah akun → Petugas pengaduan khusus PTA → Simpan perubahan**. Akun harus berasal dari PTA dan berperan admin/operator. Petugas biasa, pemantau biasa dan admin satker lain tidak mendapat akses karena hak monitoring umum. Belum ada operator operasional yang ditunjuk otomatis.

Pemeriksaan server berlaku pada daftar, detail, perubahan dan unduhan. Admin di luar PTA tidak dapat menaikkan dirinya menjadi admin pusat PTA, memberikan hak pengaduan, atau mengubah/reset sandi akun PTA yang sudah dilindungi. Perubahan hak mencabut sesi lama melalui `auth_version`; setidaknya satu admin pusat PTA aktif dipertahankan.

## Status, penyimpanan dan pemulihan

Status: **Diterima**, **Ditelaah**, **Ditindaklanjuti**, **Selesai**. Laporan selesai dapat dibuka kembali sebagai Ditelaah. Pengelola pertama menjadi penanggung jawab awal. Setiap perubahan menambah event dengan waktu dan pengelola; laporan asli tidak dapat diedit/dihapus melalui fitur ini. `lock_version` dan penguncian transaksi mencegah penimpaan perubahan pengelola lain. Saat konflik, salin pesan yang sedang ditulis lalu muat ulang halaman.

Judul, kronologi, lokasi, pihak terkait dan isi pesan/catatan dienkripsi memakai Laravel Crypt/APP_KEY. Kolom nama/kontak tetap tersedia sebagai kolom privat terenkripsi untuk kompatibilitas skema, namun pengiriman baru selalu kosong dan UI tidak menampilkannya. Satker, jenis, tanggal, status, reference dan metadata operasional tidak dienkripsi. Daftar PTA menampilkan metadata; isi rinci dibuka secara terpisah.

Bukti maksimal tiga PDF/JPG/PNG, masing-masing 5 MB. MIME dan ekstensi diperiksa server. Nama unduhan diganti `Bukti 1.pdf` dan seterusnya; nama asli tidak disimpan. Isi berkas dienkripsi di disk lokal `storage/app/private/complaints`, di luar public, dan dicek SHA-256 sebelum diunduh sebagai attachment. Akses memerlukan hak PTA atau sesi pemilik tiket; URL berkas publik tidak tersedia. Respons privat memakai no-store/no-referrer; halaman tidak diindeks.

Pengiriman berulang dengan nonce sama tidak membuat laporan baru. Kegagalan sebelum commit melakukan rollback serta pembersihan bukti baru. Jika hasil commit database ambigu dan tidak dapat diperiksa kembali, bukti terenkripsi dipertahankan untuk menghindari kehilangan bukti yang mungkin sudah sah. Isian teks dipertahankan saat validasi/kegagalan; berkas perlu dipilih ulang. Kode rahasia dan field identitas yang tidak digunakan tidak dimasukkan ke flash input. Batas pengiriman 5/IP/10 menit dan pembukaan kode 10/IP/menit; IP bukan kolom identitas laporan.

Tidak ada pengiriman otomatis WhatsApp/email, penerusan ke sistem pengaduan eksternal, SLA hukum, atau penghapusan berdasarkan retensi pada fitur ini. Penyimpanan APP_KEY, database dan berkas privat perlu disertakan dalam prosedur cadangan aplikasi; APP_KEY diperlukan untuk membaca data terenkripsi.

## Implementasi dan database

Pemilik aturan: `PublicComplaintController`, `ComplaintManagementController`, `ComplaintService`, `User::canHandleComplaints`, `User::canManageComplaintAccess`, serta `AdminAccountController`. Kontrak tampilan berada pada `UX-CONTRACT.md`; warna tetap mengikuti `DESIGN.md` dan token `public/css/app.css`.

Migrasi `2026_11_02_000015_add_complaints` menambahkan tabel laporan, lampiran dan event serta flag hak pada users. Migrasi `2026_11_02_000016_widen_encrypted_complaint_description` menggunakan MEDIUMTEXT pada MySQL agar 10.000 karakter UTF-8 beserta ekspansi enkripsi muat. Keduanya sudah diterapkan lokal. Cadangan sebelum migrasi tersedia privat sebagai `storage/app/private/ptsp-before-complaints-20261102-105139.sql`.

Tes `tests/Feature/ComplaintTest.php` memakai SQLite memory dan disk palsu. QA browser memakai database, akun, session dan storage terisolasi di luar proyek. Halaman operasional hanya dibuka melalui GET; laporan dummy dan perubahan hak QA tidak dimasukkan ke database operasional. Bukti pemeriksaan tersimpan di `C:/Users/rubik/.codex/artifacts/ptsp-complaints-20261102`.

Verifikasi akhir: 115 tes/946 assertion lulus, syntax 14 berkas PHP dan 9 berkas JavaScript lulus, Blade berhasil dikompilasi, lint DESIGN.md tanpa error/warning. Browser Chromium melalui Playwright digunakan karena plugin Browser tidak tersedia; viewport 1440×1000 dan 390×844. Pengiriman anonim, unggah/hapus pilihan bukti, unduh privat, penunjukan petugas, pencabutan sesi, penolakan akses satker, pesan internal vs pelapor, pelacakan salah/benar, penutupan perangkat, no-results/clear, keyboard native select dan preferensi reduced-motion diperiksa. Tidak ada exception JavaScript atau console error tak terjelaskan; satu respons notifikasi 401 berasal dari sesi petugas lama yang sengaja dicabut. Popup select/date mengikuti browser/OS.

Audit statis premium strict telah dicoba tetapi tidak dapat berjalan karena Python belum tersedia (alias Microsoft Store). Hal ini dicatat sebagai pemeriksaan yang belum selesai, bukan lulus. Browser lain, perangkat sentuh fisik dan audit pembaca layar penuh belum diperiksa. Bukti mesin ada pada `checks.json`, `qa.json`, `database-verification.json` dan screenshot di direktori artefak tersebut.

Pemeriksaan lanjutan `validation-qa.json` memastikan pesan persetujuan berada setelah kelompok checkbox, hilang saat dicentang, dan pesan kode rahasia berada di luar wrapper reveal. Syntax ui.js lulus setelah perbaikan; browser 390×844 tidak mencatat error JavaScript atau overflow. Server QA port 8017 sudah dihentikan; Apache/MySQL operasional tetap berjalan. Pemeriksaan akhir operasional: HTTP 200, sembilan akun tetap ada, satu admin pusat PTA, nol petugas yang ditunjuk otomatis, dan nol laporan dummy pada database operasional.
