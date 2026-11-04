# UX Contract

## Product context and sources

Masyarakat berkonsultasi dengan PTSP satker terdaftar; petugas melayani satkernya; pemantau mengikuti hak server. Bahasa id-ID, WIT Asia/Jayapura, Gregorian. Target aksesibilitas WCAG 2.2 AA; tema terang. Sumber: permintaan pengguna dalam percakapan ini, `docs/CHAT_PTSP.md`, `docs/GOOGLE_MEET.md`, `app/ChatRoom.php`, `app/Meeting.php`, controller dan aturan validasi Laravel. Kebijakan retensi/legal/billing tidak dibuat pada pekerjaan UI ini; akun dan satker dinonaktifkan tanpa menghapus riwayat; tanggal tutup dapat dihapus setelah konfirmasi. Tidak ada integrasi pembayaran.

## Sumber kebijakan pengaduan

Sumber keputusan bisnis untuk pengaduan:

| Area | Sumber otoritatif | Konsekuensi UI |
|---|---|---|
| Izin pembaca | Keputusan pengguna: admin pusat PTA dan petugas khusus yang ditunjuk; docs/PENGADUAN.md | Navigasi terbatas, otorisasi setiap route, penunjukan hanya oleh admin PTA |
| Identitas dan siklus data | Keputusan pengguna: anonim dan data pelapor rahasia; docs/PENGADUAN.md | Tanpa kolom identitas/kontak, tanpa tautan akun, akses tiket dengan kode |
| Penghapusan/retensi | Tidak diminta atau ditetapkan; docs/PENGADUAN.md | Tidak ada tombol hapus maupun janji penghapusan otomatis |
| Pembayaran | Tidak berlaku pada kanal ini | Tidak ada alur pembayaran |
| Janji layanan/legal | Tidak ditetapkan pada permintaan ini | Tidak menjanjikan tenggat hukum atau penerusan eksternal |

## Canonical UI Map

| Capability | Canonical owner | Source of truth | Allowed variants | Verification |
|---|---|---|---|---|
| Select/Listbox | Native select + .form-control | DESIGN.md | native OS popup | keyboard dan popup konsultasi |
| Date | Native consultation dropdowns + time/date inputs | docs/GOOGLE_MEET.md | dropdown penjadwalan WIT / native date hari tutup dan riwayat / time jam layanan | browser dan GoogleMeetTest |
| Form | Laravel validation + public/js/ui.js | Controller request rules | server form / existing AJAX chat | inline invalid, busy, recovery |
| Scrollbar | public/css/app.css | DESIGN.md | document / panel overflow | computed style dan narrow viewport |
| Toast | Layout session alerts + ui status | resources/views/layouts/app.blade.php | success / error / info | live-region dan dismiss |
| CRUD | Existing named Laravel routes/controllers | docs/CHAT_PTSP.md dan docs/GOOGLE_MEET.md | service create / meeting lifecycle / akun / satker / jam layanan | PHPUnit dan browser |
| Private complaint | PublicComplaintController / ComplaintManagementController | docs/PENGADUAN.md, User complaint permissions | anonymous intake / secret ticket / PTA handling | ComplaintTest dan browser terisolasi |
| Illustration | public/partials/illustration.blade.php + config/ui.php | docs/design/illustrated/README.md | hero / service tile / compact heading | alpha, dimensi, pemuatan dan responsivitas browser |

## Navigation and state

Perluasan UI 3 November 2026: dokumen docs/design/interior-ui.md memiliki tiga patokan tambahan untuk publik, petugas dan akses akun. Semua route terkait dimuat melalui shell kanonis dan product-ui.css; judul tugas serta panduan berbagi partial. Angka panduan dekoratif aria-hidden. Pemulihan/verifikasi tetap memakai endpoint lama, sekarang berbahasa Indonesia dengan judul halaman yang sesuai. Login mengarahkan masyarakat ke konsultasi tamu, bukan ke form identifikasi lama. Tidak mengubah verifikasi identitas, tarif, hak akses atau transisi layanan.

Jam layanan mempertahankan scroll tabel pada operations-table-wrap yang menjadi containing block bagi label tersembunyi. Form dan wrapper halaman tidak dikunci tinggi atau disembunyikan overflow-nya untuk menutupi tabel. Sidebar memakai varian putih, aktif peach, dengan perilaku drawer/fokus yang sama. Pengujian browser terbaru memakai SQLite terisolasi karena database utama crash; keterbatasan ini dicatat terpisah dari hasil tes aplikasi.

Navigasi publik memakai service-links bersama untuk header, rel kanal interior dan galeri bergambar. Patokan aktif 4 November: docs/design/illustrated/README.md. Beranda menampilkan judul/ilustrasi, form #pilihWilayah melintang, lalu empat layanan bergambar (finder tidak diulang karena sudah ada form); kelima kanal tetap ada di header. Galeri mempertahankan parameter region/unit. Pada ponsel ilustrasi diperkecil menjadi 200px sebelum form, dan CTA Cari pengadilan langsung memfokuskan form agar gambar dapat dilewati. Gambar dekoratif memiliki alt kosong/aria-hidden, dimensi eksplisit dan object-fit:contain. Header sempit menggulir internal dan Login petugas tetap terlihat. Hasil #hasil, konsultasi tamu dan pengaduan tetap ditentukan server.

Region-picker menyediakan varian requireProvince untuk form pencarian pengadilan: pemilihan pertama memakai validasi inline/fokus milik ui.js, sedangkan turunan tetap opsional mengikuti aturan pencarian server yang sudah ada. Pesan fallback validasi region dari PublicPortalController berbahasa Indonesia. Tidak menerapkan kewajiban baru pada pemilih wilayah di form lain.

Judul tiap route mengikuti Blade title. Menu aktif memakai aria-current. Drawer seluler menutup dengan Escape, backdrop, atau navigasi; mengembalikan fokus tombol menu. Sidebar tidak menutup akses keyboard ke isi halaman. Formulir panjang scroll pada dokumen; tabel pada wrapper horizontal. Search navigasi header bersifat sementara, bukan pencarian server. Katalog menyimpan pencarian/kategori di query string dan menyediakan clear, jumlah hasil, dan no-results. Katalog berisi data aktif yang telah dimuat, filter lokal; tidak mengklaim pagination server. Daftar satker berasal dari tabel work_units; admin pusat dapat menambah satker. Monitoring dan ringkasan antrean mengikuti scope server.

## Forms and feedback

Form menggunakan novalidate, label terhubung, pesan inline dan fokus field invalid pertama. Aturan server tetap otoritatif. Mask kata sandi default, reveal button dengan label berubah, tidak menyimpan isi. Busy submit tidak mengubah ukuran tombol dan tidak menghilangkan parameter tombol seperti decision. Form AJAX yang sudah ada tetap mengelola error/pending sendiri. Native select dipilih untuk akses keyboard dan keandalan; geometri popup milik OS diterima.

Kontrol rahasia memakai primitive reveal yang sama dengan label lokal sesuai jenis rahasia. Kode tiket dimask, tidak masuk URL atau flash input. ui.js memetakan error server pada unggahan array (evidence/evidence.0) ke kontrol evidence[] yang tepat. Varian data-clear-submit pada pencarian GET memuat ulang hasil server setelah tombol clear digunakan, sambil mempertahankan filter lain. complaints.js hanya memiliki pemilihan/penghapusan bukti, salin kode dan pemeriksaan kembali akses halaman privat ketika dipulihkan dari back-forward cache.

Pesan error untuk kelompok checkbox dan input rahasia ditempatkan setelah wrapper kontrol, sehingga tidak menjadi kolom tambahan pada baris persetujuan atau menggeser tombol reveal.

Shared modal memakai dialog semantics, fokus awal, trap Tab, Escape, inert background dan pengembalian fokus. Penambahan layanan mempertahankan input saat gagal. Tidak memakai alert/confirm/prompt native. Pembatalan konsultasi menggunakan dialog konfirmasi milik aplikasi sebelum POST/DELETE; server menentukan hasil. Pesan kritis tetap inline dan dapat dicoba kembali.

## Flow ledger

| Operation | Trigger | Pending | Success destination | Failure recovery | Source ref |
|---|---|---|---|---|---|
| Service create | Simpan layanan | busy submit | katalog + session success | modal/old input dan error | ServiceController |
| Search | input/kategori | filter lokal langsung | katalog query string | clear/reset dan no-results | services view + ui.js |
| Chat | Buka percakapan / Konsultasi | existing AJAX send | room satker | isi pesan dipertahankan | docs/CHAT_PTSP.md |
| Meeting | Jadwalkan konsultasi | busy submit | daftar / antrean / Google Meet sesuai server | old input dan error | docs/GOOGLE_MEET.md |
| Meeting cancel | Batalkan jadwal | confirmation, busy POST | daftar dan session message | retry setelah server failure | MeetingController |

## Migration and verification

Penataan UI awal memperbaiki shell bersama, dashboard, katalog, login serta beranda. Pengembangan operasional berikutnya menambahkan API ketersediaan/antrean, pengelolaan akun/satker, serta pembatasan jadwal sebagaimana ledger di bawah. Legacy scaffold CSS di resources/sass bukan sumber runtime; jangan menjalankan build yang menimpa public/css/app.css. Pemeriksaan: lint DESIGN.md, strict premium audit, node --check, kompilasi Blade, PHPUnit, browser desktop/390px, keyboard, invalid/empty/no-results, native select popup, serta reduced-motion. Bukti browser dan keterbatasan dicatat di docs/UI_REVIEW.md.


## Operations flow ledger (31 Oktober 2026)

| Operation | Trigger | Pending | Success destination | Failure recovery | Source ref |
|---|---|---|---|---|---|
| Account create/update | Buat akun / Simpan perubahan | validasi dan busy submit | halaman edit + session success | old input kecuali password; summary + error field | AdminAccountController, PtspOperationsTest |
| Unit create/update | Simpan satker | validasi dan busy submit | halaman edit + session success | old input; nama identitas tetap pada edit | AdminWorkUnitController |
| Hours | Simpan jam layanan | validasi interval, busy submit | halaman jam + session success | old input 7 hari; error terhubung field | ServiceHoursController |
| Holiday create/remove | Tambah tanggal / Hapus | validasi / app confirmation + busy | halaman jam + session success | input dipertahankan, Escape mengembalikan fokus | ServiceHoursController, ui.js |
| Booking availability | Ganti tanggal | select/tombol pengajuan disabled, status memuat | opsi sesuai server, WIT, kuota tersisa | status inline, Coba muat waktu; abort/stale-response guard | availability.js, ServiceHoursService |
| Queue refresh | Muat ulang / tiap 10 detik | satu request, tombol busy | fragment Blade yang sama dengan render awal | data terakhir dipertahankan, retry; 401/403 meminta masuk kembali | operations.js, QueueDashboardController |
| Queue verification/service | Verifikasi / Tolak / Layani / Selesai | server guard, shared busy; Tolak memakai konfirmasi | antrean satker sendiri + pesan hasil | tidak ada mutasi lintas satker; server validasi urutan | MeetingController, PtspOperationsTest |

Antrean aktif mencakup seluruh antrean jatuh tempo, termasuk hari sebelumnya. Tab pending mencakup seluruh pengajuan belum diverifikasi; tab riwayat memakai tanggal jadwal WIT dan pagination server 15 data. Penggantian fragment ditunda saat kontrol di dalamnya berfokus, dialog terbuka, atau form sedang dikirim. API JSON tidak di-cache. Pemantau hanya mendapat tampilan baca saja untuk satker lain.

Pengaturan jam/kuota belum diterapkan pada data lama sampai petugas mengaktifkannya. Jendela pemesanan adalah waktu mulai: jam buka termasuk, jam tutup tidak termasuk, awal istirahat tidak termasuk, akhir istirahat termasuk. Pilihan waktu berjarak 15 menit. Kuota menghitung pending, aktif, dan selesai pada tanggal jadwal; cancelled/rejected melepaskan kuota. Tidak menjanjikan durasi tunggu tertentu atau menutup pemesanan lama secara otomatis.

Akun nonaktif tidak dapat login melalui email atau nomor HP. Perubahan sandi/akses oleh admin mengganti auth_version dan remember token untuk mengakhiri sesi lama pada permintaan berikutnya. Admin tidak dapat menghapus akses manajemen akun yang sedang dipakai; sedikitnya satu admin pusat harus aktif. Nama satker tidak diubah setelah dibuat agar snapshot riwayat tetap terhubung. Staff assignment pada percakapan/jadwal aktif dilepaskan saat akses berubah agar tim satker tetap dapat melayani. Jejak perubahan tidak menyimpan password/hash.

Verifikasi tambahan: PtspOperationsTest; browser pada admin/petugas/masyarakat, penyimpanan satker sementara, jam/istirahat/kuota, hari tutup, dropdown hari tutup vs hari layanan, booking mendatang, verifikasi, polling, Layani/Selesai, dialog Escape, dan viewport desktop/ponsel. Data uji sementara dibersihkan berdasarkan ID setelah pemeriksaan. Antrean Google Meet yang nyata tidak dibuka saat QA.


## Public access contract

Varian pencarian perkara: `CaseStatusController`, `CaseStatusLookup`, dan docs/CEK_STATUS_PERKARA.md. Form server-rendered dua isian memakai native select, ui.js untuk validasi/busy/status/clear pencarian, serta shell publik yang sama. POST mempertahankan input pada hasil dan kegagalan; pencarian dibatasi 10/IP/menit. Hasil ditemukan, tidak ditemukan pada data tersinkron, sumber tidak tersedia, dan pembatasan sementara dibedakan; tidak ada timeline atau tanggal proses rekaan. Menu Cek status perkara dan akta cerai tetap aktif pada hasil POST. Hanya ringkasan tanpa identitas/dokumen, dan konsultasi memakai satker serta topik perkara yang dipilih. Tidak ada verifikasi pihak. Bagian akta cerai memakai snapshot final kasuari_akta_cerai dengan timestamp terpisah; penerbitan dan pengambilan pihak 1/2 independen, data kosong berarti belum tercatat, bukan belum diambil. Nomor akta, identitas dan dokumen tidak ditampilkan. API lama atau gangguan data akta tetap mempertahankan ringkasan perkara.

Sumber keputusan dan batas integrasi: docs/AKSES_PUBLIK.md. PublicPortalController dan region-picker berbagi pemilihan wilayah native. public-portal.js mengelola request abort/stale guard, retry, status tiket dan pesan yang di-escape melalui textContent. ui.js tetap pemilik validasi, dialog, busy dan pesan server; validasi submit membaca field dinamis terbaru. Tanggal/waktu Meet mengikuti availability.js dan ServiceHoursService. Sesi tamu tidak mengautentikasi akun melalui nomor. Tiket lengkap perlu kepemilikan sesi; undangan hanya untuk satu Meet dan GET read-only. Panggil tamu tidak memulai pelayanan; Mulai layani merupakan transisi eksplisit. PublicDataController membatasi pemetaan ke admin pusat dan tarif ke petugas satker sendiri/admin pusat. Tarif tidak lengkap tidak dihitung sebagai nol. Fonnte menyimpan outbox dan callback dini, tidak mengulang hasil yang ambigu.

## Pengaduan anonim (2 November 2026)

Sumber kebijakan, cakupan satker, hak pembaca, penyimpanan dan batas retensi: docs/PENGADUAN.md, berdasarkan keputusan pengguna. UI tidak meminta identitas/kontak dan tidak menyalin akun yang sedang login. Lima tab publik mempertahankan menu aktif pada halaman tiket dan pelacakan. Petugas satker tidak mendapat pintasan atau akses kotak masuk PTA; hak monitoring umum bukan izin pengaduan.

| Operation | Trigger | Pending | Success destination | Failure recovery | Source ref |
|---|---|---|---|---|---|
| Pengaduan baru | Kirim pengaduan ke PTA | shared inline validation + busy | tiket sendiri dan receipt kode sekali tampil | isian dipertahankan; pilih ulang berkas; nonce sama tidak menduplikasi | PublicComplaintController, ComplaintService |
| Lacak tiket | Buka laporan saya | shared busy; kode dimask | satu tiket milik pemegang kode | pesan salah nomor/kode seragam; kode tidak diisi ulang | PublicComplaintController |
| Tutup akses perangkat | Tutup akses di perangkat ini | shared busy POST | form pelacakan; laporan tetap ada | dapat dibuka dengan nomor+kode; reload saat BFCache | PublicComplaintController, complaints.js |
| Tindak lanjut PTA | Simpan tindak lanjut | validasi + busy + optimistic version | detail dan timeline baru | konflik meminta salin pesan lalu muat ulang; internal note tetap khusus PTA | ComplaintManagementController |
| Penunjukan petugas | Simpan perubahan akun | aturan hak server + busy | akun dengan hak yang disimpan | sesi lama dicabut; penunjukan di luar wewenang ditolak | AdminAccountController, User |

Daftar PTA memakai pagination server 15 laporan, filter URL dan pencarian nomor lengkap dengan clear. Daftar tidak menampilkan judul/kronologi. Detail menampilkan laporan asli read-only, unduhan bukti dan timeline. Pesan untuk pelapor terpisah jelas dari catatan internal; data internal tidak dimuat ke view pelapor. Penghapusan laporan, identifikasi pelapor dan reset kode bukan affordance yang ditawarkan. Panel petunjuk menjelaskan kehilangan kode tanpa menjanjikan pemulihan yang tidak tersedia.

## Konsultasi ringkas · 4 November 2026

Halaman konsultasi memakai satu form terpusat tanpa rel navigasi kedua dan tanpa panel panduan samping. Petunjuk konsultasi menggunakan details/summary native, tertutup awal dan dapat dibuka dengan keyboard. Persetujuan tetap terlihat sebelum tombol Ajukan konsultasi; nomor WhatsApp tetap wajib untuk Meet dan opsional untuk chat. Seluruh ID/data-attribute, error inline, ketersediaan jadwal WIT, tiket sesi, konteks wilayah/perkara/biaya dan link pengaduan tetap memakai pemilik yang sama. Perubahan ini hanya pada varian konsultasi, sesuai konteks halaman pengguna.

## Identitas dan navigasi PACE (4 November 2026)

Nama aplikasi berasal dari config/ui.php dan lockup bersama partials/brand.blade.php. Header publik mengikuti docs/design/pace/README.md: navigation.js memiliki disclosure inline <=1050px dengan aria-expanded/controls, Escape/fokus, klik luar dan normalisasi breakpoint. Navigasi tetap terbuka tanpa JavaScript; bukan modal dan tidak menjebak fokus. Varian ini menggantikan header publik gulir horizontal lama. Drawer petugas tetap milik app.js. Footer menghapus baris cakupan wilayah; data wilayah dan pemetaan satker tidak berubah.

Penyesuaian header berikutnya: hapus partial tabs beserta pemanggil di perkara, biaya, pengaduan baru dan pelacakan pengaduan. Header fixed diaktifkan oleh navigation.js, dengan ResizeObserver dan spacer tinggi aktual termasuk menu seluler. Fallback tanpa JavaScript tetap menampilkan navigasi dalam aliran halaman. Offset scroll/fokus main mencegah tujuan anchor tertutup. Menu tetap disclosure inline, bukan modal. Tidak mengubah pencarian perkara atau hak akses.

## Desain E hijau–emas disetujui (4 November 2026)

Acuan visual aktif docs/design/green-gold/home-reference.png. Token hijau–emas app.css berlaku pada publik, petugas dan akses akun. Beranda memakai dua kolom ilustrasi/form, empat dropdown vertikal, empat pintasan bergambar; pada ponsel judul, ilustrasi, form, pintasan. Dua CTA hero lama dan caption dekoratif sumber dipangkas agar sama dengan konsep; rujukan wilayah pada hasil tetap terlihat. Source version dan database tidak berubah. Header fixed/disclosure memakai owner lama, tanpa rel navigasi tambahan. Perubahan hanya presentasi; validasi, hierarki wilayah, izin dan alur konsultasi/pengaduan tetap kanonis.
