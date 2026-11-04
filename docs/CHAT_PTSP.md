# Chat PTSP per satuan kerja

Perubahan 27 Oktober 2026 mengarahkan setiap pendaftar ke PTSP satuan kerja yang dipilihnya.

## Alur masyarakat

- Pendaftaran nomor HP maupun email wajib memilih satuan kerja. Sistem menyiapkan percakapan untuk PTSP satker tersebut dalam transaksi yang sama dengan pembuatan akun.
- Menu **Chat PTSP** langsung membuka percakapan aktif; masyarakat tidak perlu memilih petugas. Akun lama yang masuk melalui nomor HP juga disiapkan percakapannya.
- Tombol **Konsultasi** dari katalog layanan memakai satker pendaftar dan menyertakan konteks layanan. Percakapan aktif untuk layanan yang sama digunakan kembali.
- Mode suara menggunakan alur penugasan yang sama dan tidak membuka kembali percakapan yang sudah ditutup.
- Petugas juga dapat membuka Voice to Chat, memilih percakapan masyarakat sesatker melalui dropdown, lalu mendiktekan atau mengetik balasan. Menu tidak lagi mengarahkan petugas kembali ke daftar chat.
- Akun lama tanpa satker diarahkan untuk memilih satker terlebih dahulu.

## Kotak masuk satker

`chat_rooms.work_unit` menyimpan tujuan layanan pada percakapan. Operator/admin sesatker dapat melihat dan melayani kotak masuk bersama, termasuk percakapan yang ditugaskan kepada petugas sesatker lainnya. `admin_id` menyimpan petugas penanggung jawab; pilihan awal mengutamakan petugas yang ditandai online, lalu operator, dengan urutan ID sebagai penentu tetap.

Petugas di luar satker tidak dapat membuka, mengambil pesan, atau membalas percakapan. Staf dengan `can_monitor` tetap dapat membaca percakapan lintas satker untuk pemantauan, tetapi tidak dapat membalas untuk satker lain. Pemantauan lintas satker tidak menandai pesan masyarakat sebagai sudah dibaca.

Jika petugas satker belum tersedia, percakapan tetap masuk antrean satker dengan `admin_id` kosong. Tidak ada pengalihan ke pusat atau satker lain. Petugas yang ditambahkan kemudian langsung dapat membuka antrean satkernya; balasan pertama dapat mengisi penanggung jawab yang masih kosong.

Notifikasi staf menghitung pesan masyarakat yang belum dibaca dalam cakupan kotak masuknya. Membaca percakapan oleh petugas lokal menandai pesan masuk sebagai dibaca bersama; balasan antarpengguna staf tidak dihitung sebagai pesan masyarakat baru.

## Riwayat dan migrasi

Migrasi `2026_10_27_000008_add_work_unit_to_chat_rooms_table` menambahkan kolom tujuan beserta indeks. Percakapan lama diisi menggunakan satker pemiliknya. Penugasan percakapan terbuka yang kosong atau tidak sesuai satker diarahkan ulang ke petugas lokal. Pesan, pemilik, layanan, status, dan timestamp lama dipertahankan.

Migrasi sudah diterapkan ke database lokal. Cadangan sebelum migrasi disimpan di `storage/app/private/chat-routing-before-*.json`, di direktori yang diabaikan Git dan dilindungi dari HTTP Apache. Tidak ada penghapusan percakapan lama atau penggabungan riwayat ganda secara otomatis.

## Verifikasi

- Seluruh 31 tes proyek lulus dengan 184 assertion. `ChatRoutingTest` menggunakan SQLite di memori.
- Kasus yang diuji meliputi kelima satker, pendaftaran nomor/email, antrean tanpa petugas, petugas offline, pelayanan tim sesatker, penolakan akses lintas satker/pengguna, pemantauan pusat, penggunaan ulang room, percakapan tertutup, serta migrasi riwayat.
- Pengujian HTTP lokal untuk kelima satker berhasil melewati pendaftaran, redirect langsung ke chat, pengiriman pesan, notifikasi lokal, balasan, pengambilan balasan, dan penolakan akses operator satker lain. Akun dan pesan sementara pengujian sudah dihapus.
- URL polling chat dan redirect mode suara dibuat melalui route Laravel agar bekerja pada instalasi subfolder XAMPP. Pesan baru dirender sebagai teks; cursor polling tidak dilompati ketika pesan dikirim.

Status kehadiran staf masih memakai field `is_online` yang sudah ada; pengalihan chat selalu tetap pada satker walaupun seluruh petugas ditandai offline.

## Pengecualian kanal pengaduan (2 November 2026)

Layanan berkategori `pengaduan` atau `gratifikasi` sekarang diarahkan ke formulir anonim PTA, bukan percakapan petugas satker. Pengecualian berlaku pada kartu katalog dan server `ChatController@createRoom`. Percakapan lama dipertahankan. Kebijakan dan alur terpisah berada di `docs/PENGADUAN.md`.
