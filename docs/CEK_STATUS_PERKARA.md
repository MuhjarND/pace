# Cek status perkara dan akta cerai

Keputusan pengguna 1 November 2026: satu menu publik untuk ringkasan status perkara serta penerbitan/pengambilan akta cerai, tanpa login, cukup pengadilan yang menangani dan nomor perkara lengkap. Pengisian tersebut bukan verifikasi bahwa pengunjung adalah pihak berperkara. URL `/cek-status-perkara` dipertahankan agar tautan lama tetap berlaku.

## Alur dan batas data

- GET `/cek-status-perkara` menampilkan form dua isian. Menu tersedia di portal publik dan sidebar ruang kerja.
- POST ke alamat yang sama memakai CSRF, validasi nomor lengkap, dan batas 10 percobaan per IP per menit. Nomor tidak dimasukkan ke URL pencarian atau disimpan sebagai riwayat di database PTSP. Hasil memakai `Cache-Control: no-store, private`, no-referrer, dan noindex.
- Server PTSP memanggil Kasuari memakai header privat `X-PTSP-Key`. Browser tidak menerima token atau akses langsung ke database/API Kasuari.
- Data publik dibatasi dua kali, di API dan konsumen: nomor, jenis perkara, tanggal daftar, tanggal putusan, tahapan terakhir, proses terakhir. Nama pihak, NIK, alamat, nomor kontak, posita, petitum, amar, dan dokumen tidak dimuat. Nilai HTML di-escape oleh Blade.
- Akta cerai hanya menambahkan status penerbitan, tanggal terbit, status/tanggal penyerahan tiap pihak, dan waktu sinkronisasi akta. Nomor/seri akta, nama penerima, identitas, dan tautan dokumen tetap tidak dikirim ke portal publik.
- Identitas pengadilan dicocokkan dengan nama satker aktif yang dinormalisasi (`PA Sorong` / `Pengadilan Agama Sorong`, termasuk variasi spasi/tanda hubung Fakfak). Tidak mengasumsikan ID WorkUnit PTSP sama dengan `pengadilan_agama.id` Kasuari. Nama ambigu atau nomor perkara duplikat menghasilkan unavailable.
- Tanggal putusan memakai nilai terbaru untuk pasangan `(pn_id, perkara_id)`; ID perkara satker tidak dianggap unik lintas pengadilan.
- Waktu sinkronisasi berasal dari `sync_monitoring.last_sync_at`, dengan fallback `MAX(log_singkron.tanggal)` satker yang sama, dalam WIT. Waktu heartbeat/permintaan sekarang tidak dipakai sebagai waktu sinkronisasi. Jika metadata tidak tersedia, tampilkan keterangan belum tersedia.
- Dataset satker kosong, sumber terputus, respons tidak valid, autentikasi integrasi gagal, atau respons pengadilan/nomor tidak cocok menghasilkan HTTP 503 dengan isian tetap tersedia. Dataset satker berisi tetapi nomor tidak ditemukan menghasilkan hasil tidak ditemukan pada data tersinkron, tanpa menyimpulkan nomor tidak sah.
- Tombol konsultasi membawa satker dan nomor perkara sebagai topik yang bisa diedit masyarakat. Ini bukan pemberian hak akses atau konfirmasi kepemilikan perkara. Hanya identitas publik perkara yang ada pada URL konsultasi, tanpa token.

## Makna status akta cerai

Sumber adalah snapshot final `kasuari_akta_cerai` dengan marker `kasuari_akta_cerai_sync` untuk satker yang sama. Relasi selalu `(pn_id, perkara_id)`. Tabel staging tidak dibaca. Interpretasi penerbitan memakai helper `kasuari_akta_view` yang sudah ada; otorisasi halaman internal Kasuari tetap dipertahankan.

| Kondisi | Tampilan publik |
|---|---|
| Nomor dan tanggal terbit tercatat | Akta terbit tercatat; tanggal ditampilkan |
| Hanya sebagian metadata terisi | Data akta belum lengkap |
| Snapshot selesai tetapi akta belum tercatat | Akta belum tercatat pada data tersinkron |
| Belum pernah sinkron / tabel / koneksi akta tidak tersedia | Informasi akta cerai belum tersedia; status perkara tetap ditampilkan |
| Ada tanggal penyerahan pihak terkait | Sudah diambil (tercatat), disertai tanggal penyerahan |
| Kolom penyerahan tersedia, tanggal kosong | Pengambilan belum tercatat |
| Kolom pihak 2 tidak tersedia pada SIPP sumber | Informasi pengambilan belum tersedia |

Pihak 1 dan pihak 2 ditampilkan terpisah tanpa nama. Tidak menyimpulkan siap diambil dari penerbitan, tidak menyimpulkan belum diambil dari tanggal kosong, dan tidak menganggap penyerahan SIPP sebagai unduhan EAC. Waktu sinkronisasi akta terpisah dari waktu sinkronisasi perkara. Pada perkara nonperceraian tanpa catatan akta, panel akta disembunyikan sebagaimana pola Kasuari yang ada.

## Pemasangan Kasuari

Perubahan pendamping berada di `C:/xampp/htdocs/kasuari`:

1. `modul/public_case_status.php`: endpoint POST `/public_case_status`, versi kontrak 1, autentikasi token sebelum koneksi database; JSON untuk 401/405/422/503.
2. `sys/sys_public_case_status.php`: helper SELECT dengan prepared statement dan proyeksi data publik. Tidak memakai endpoint `/sync`, query dari pengunjung, atau helper pembuatan schema.
3. `sys/sys_public_case_status.local.example.php`: contoh konfigurasi. File privat `sys/sys_public_case_status.local.php` mengembalikan array `token` berisi secret acak minimal 48 karakter. `.htaccess` dan `.gitignore` melindunginya.
4. Helper akta yang sudah ada, `sys/sys_akta_cerai.php`, menjadi pemilik interpretasi penerbitan. Jika helper atau schema akta belum terpasang, API tetap dapat menyajikan perkara dan menandai akta belum tersedia. Pembaca publik tidak menjalankan migrasi/schema creation.

Endpoint memakai konfigurasi database Kasuari yang sudah ada, lalu transaksi `READ ONLY`. Tidak ada migrasi, pengiriman ulang data, atau perubahan `kasuari_satker` yang diperlukan untuk ringkasan status karena tabel `perkara`/`perkara_putusan` sudah termasuk sinkronisasi. Ini tidak menyatakan seluruh isi satker sudah lengkap; sumber tetap harus tersedia dan pernah menerima data.

Status akta memanfaatkan paket Kasuari `deploy/satker-akta-cerai/README.md` yang sudah tersedia. Satker harus pernah berhasil mengirim snapshot akta melalui paket tersebut. Pekerjaan portal ini tidak mengubah sinkronisasi atau database SIPP. API versi 1 diperluas secara aditif dengan `divorce_certificate`: `state`, `issued_on`, `synced_at` (ISO WIT), dan `collections.party1/party2` (`state`, `collected_on`). PTSP tetap kompatibel dengan API versi lama yang belum menyertakan bagian ini.

Pada PTSP, isi `KASUARI_CASE_STATUS_URL` dan `KASUARI_CASE_STATUS_TOKEN` di `.env`, lalu `php artisan config:clear`. URL harus HTTPS; HTTP diizinkan hanya untuk localhost/127.0.0.1/::1 saat pengembangan. Redirect upstream tidak diikuti agar header rahasia tidak diteruskan ke host lain. Secret hanya ada pada konfigurasi server, tidak dicantumkan dalam dokumen ini.

Konfigurasi kedua aplikasi sudah dipasangkan untuk pengembangan lokal di `http://localhost/kasuari/public_case_status`. Untuk hosting, unggah perubahan pendamping ke server Kasuari dan pasangkan secret privat serta URL HTTPS yang benar. Tidak ada deployment hosting atau permintaan ke API produksi pada pekerjaan ini.

## Verifikasi dan kondisi lingkungan

`tests/Feature/CaseStatusTest.php` memakai SQLite terisolasi dan HTTP fake untuk hasil ditemukan/tidak ditemukan, timeout/error, respons tidak valid, redaksi data privat, validasi, batas percobaan, dan konteks konsultasi. `C:/xampp/htdocs/kasuari/tools/tests/public_case_status_test.php` menguji query helper dengan SQLite terisolasi, relasi lintas satker, metadata, duplikasi, dan pembatasan data. Data uji tidak menjadi hasil di portal operasional.

Saat diperiksa pada pengembangan gabungan ini, koneksi database Kasuari melalui konfigurasi lokal ditolak MySQL (1045). Karena itu sumber nyata masih menampilkan kondisi tidak tersedia. Ini bukan bukti bahwa Kasuari produksi tidak memiliki data akta; isi produksi tidak diakses. Jangan mengganti kondisi ini dengan data dummy atau mengimpor dump SIPP ke database PTSP.

Pemeriksaan akhir: 90 tes aplikasi/702 assertion lulus, ditambah 33 assertion helper API Kasuari. Sintaks PHP yang berubah, delapan file JavaScript, kompilasi Blade, dan lint DESIGN.md lulus (0 error/0 warning). Browser menggunakan Playwright karena Browser plugin tidak tersedia; viewport 1440×1000 dan 390×844 menguji validasi/fokus, clear, gangguan sumber nyata, busy, hasil ditemukan/tidak ditemukan pada fixture, konsultasi sesuai satker/topik, serta reduced motion. Tidak ada exception JavaScript; console 503 pada pencarian sumber nyata adalah kondisi sumber yang sengaja ditangani. Pada pemeriksaan gabungan terakhir tidak ada kegagalan aset CDN. Aset CDN asli diteruskan melalui curl selama QA karena transport Chromium ke CDN kadang timeout. Bukti berada di `C:/Users/rubik/.codex/artifacts/ptsp-case-akta/qa.json` dan PNG pada direktori yang sama, di luar repository.

Audit statis premium `audit_project.py --mode strict` telah dicoba tetapi belum berjalan karena Python tidak terpasang pada lingkungan ini. Ini tidak dihitung sebagai audit lulus; pemeriksaan runtime dan lint desain di atas dilakukan terpisah.
