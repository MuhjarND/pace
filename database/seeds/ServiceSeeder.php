<?php

use App\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        // Sample catalog for local development; existing services are preserved.
        $services = [
            ['name' => 'Pendaftaran Perkara Cerai Gugat', 'description' => 'Informasi awal pengajuan cerai gugat, kelengkapan dokumen, dan alur pendaftaran melalui petugas PTSP.', 'icon' => 'fa-file-signature', 'category' => 'perkara'],
            ['name' => 'Pendaftaran Perkara Cerai Talak', 'description' => 'Konsultasi awal pengajuan cerai talak dan panduan administrasi pendaftaran perkara.', 'icon' => 'fa-gavel', 'category' => 'perkara'],
            ['name' => 'Permohonan Isbat Nikah', 'description' => 'Informasi layanan permohonan isbat nikah dan konsultasi kelengkapan administrasi dengan petugas.', 'icon' => 'fa-ring', 'category' => 'perkara'],
            ['name' => 'Permohonan Dispensasi Kawin', 'description' => 'Informasi awal permohonan dispensasi kawin dan alur konsultasi administrasi perkara.', 'icon' => 'fa-file-circle-question', 'category' => 'perkara'],
            ['name' => 'Permohonan Penetapan Ahli Waris', 'description' => 'Konsultasi informasi permohonan penetapan ahli waris dan kelengkapan dokumen administrasi.', 'icon' => 'fa-users', 'category' => 'perkara'],
            ['name' => 'Pendaftaran Perkara melalui e-Court', 'description' => 'Panduan penggunaan layanan e-Court untuk pendaftaran perkara dan konsultasi kendala penggunaan aplikasi.', 'icon' => 'fa-laptop-file', 'category' => 'perkara'],
            ['name' => 'Informasi Layanan Berperkara secara Prodeo', 'description' => 'Konsultasi ketersediaan layanan pembebasan biaya perkara dan informasi pengajuannya kepada petugas.', 'icon' => 'fa-hand-holding-heart', 'category' => 'perkara'],
            ['name' => 'Informasi Jadwal Sidang', 'description' => 'Permintaan informasi jadwal, ruang sidang, serta perubahan jadwal perkara kepada petugas.', 'icon' => 'fa-calendar-check', 'category' => 'persidangan'],
            ['name' => 'Informasi Pelaksanaan Mediasi', 'description' => 'Informasi jadwal mediasi dan petunjuk komunikasi dengan petugas yang menangani administrasi mediasi.', 'icon' => 'fa-handshake', 'category' => 'persidangan'],
            ['name' => 'Informasi Persidangan Elektronik', 'description' => 'Konsultasi penggunaan persidangan elektronik dan bantuan terkait kendala akses layanan.', 'icon' => 'fa-video', 'category' => 'persidangan'],
            ['name' => 'Permohonan Salinan Putusan/Penetapan', 'description' => 'Informasi permintaan salinan putusan atau penetapan serta pengecekan kesiapan dokumen melalui petugas.', 'icon' => 'fa-copy', 'category' => 'kepaniteraan'],
            ['name' => 'Pengambilan Akta Cerai', 'description' => 'Informasi ketersediaan akta cerai dan konsultasi administrasi pengambilan dokumen.', 'icon' => 'fa-file-circle-check', 'category' => 'kepaniteraan'],
            ['name' => 'Legalisasi Dokumen Pengadilan', 'description' => 'Informasi layanan legalisasi dokumen pengadilan dan konsultasi proses administrasinya.', 'icon' => 'fa-stamp', 'category' => 'kepaniteraan'],
            ['name' => 'Informasi Pengembalian Sisa Panjar', 'description' => 'Permintaan informasi sisa panjar biaya perkara dan konsultasi tindak lanjut pengembaliannya.', 'icon' => 'fa-money-bill-transfer', 'category' => 'kepaniteraan'],
            ['name' => 'Layanan Pengaduan dan Informasi PTSP', 'description' => 'Kanal konsultasi umum untuk pertanyaan layanan PTSP dan arahan penyampaian pengaduan.', 'icon' => 'fa-comments', 'category' => 'informasi'],
            ['name' => 'Informasi Biaya Perkara', 'description' => 'Konsultasi informasi biaya perkara dan penjelasan komponen biaya melalui petugas terkait.', 'icon' => 'fa-calculator', 'category' => 'informasi'],
            ['name' => 'Informasi Pos Bantuan Hukum', 'description' => 'Informasi ketersediaan, jadwal, dan cara mengakses layanan Pos Bantuan Hukum di pengadilan.', 'icon' => 'fa-scale-balanced', 'category' => 'informasi'],
            ['name' => 'Layanan Informasi Publik', 'description' => 'Permintaan informasi publik mengenai profil pengadilan, layanan, dan kanal komunikasi resmi.', 'icon' => 'fa-circle-info', 'category' => 'informasi'],
            ['name' => 'Penyampaian Pengaduan Layanan', 'description' => 'Konsultasi penyampaian keluhan terkait pelayanan serta informasi kanal pengaduan yang tersedia.', 'icon' => 'fa-bullhorn', 'category' => 'pengaduan'],
            ['name' => 'Pemantauan Tindak Lanjut Pengaduan', 'description' => 'Permintaan informasi perkembangan penanganan pengaduan yang telah disampaikan kepada petugas.', 'icon' => 'fa-clipboard-check', 'category' => 'pengaduan'],
        ];

        DB::transaction(function () use ($services) {
            foreach ($services as $service) {
                Service::firstOrCreate(
                    ['name' => $service['name']],
                    array_merge($service, ['is_active' => true])
                );
            }
        });
    }
}
