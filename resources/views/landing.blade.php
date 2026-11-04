@extends('layouts.app') 

@section('title', 'Beranda')

@section('styles')
<style>
    .landing-page {
        width: 100%;
    }

    .hero-section {
        min-height: 620px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
        padding: 64px 20px 88px;
        gap: 64px;
        position: relative;
        z-index: 10;
    }
    .hero-content {
        max-width: 620px;
        animation: none;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        background: rgba(var(--primary-rgb), 0.15);
        border: 1px solid rgba(var(--primary-rgb), 0.35);
        font-size: 13px;
        color: var(--primary-light);
        margin-bottom: 20px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .hero-title {
        font-size: clamp(32px, 3.5vw, 50px);
        line-height: 1.2;
        letter-spacing: -1.8px;
        font-weight: 800;
        margin-bottom: 20px;
        color: var(--text);
        text-shadow: none;
    }
    .hero-subtitle {
        font-size: 16px;
        color: var(--text-muted);
        margin-bottom: 32px;
        line-height: 1.6;
    }
    .hero-actions {
        display: flex;
        justify-content: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-lg-custom {
        padding: 15px 40px;
        font-size: 1.2rem;
        border-radius: 12px;
        background: var(--primary);
        color: var(--on-primary);
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(var(--primary-rgb), 0.4);
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .btn-lg-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(var(--primary-rgb), 0.6);
        color: var(--on-primary);
        text-decoration: none;
    }
    .btn-secondary-custom {
        padding: 15px 28px;
        font-size: 1rem;
        border-radius: 12px;
        background: var(--surface);
        color: var(--text);
        border: 1px solid var(--glass-border);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }
    .btn-secondary-custom:hover {
        transform: translateY(-4px);
        color: var(--primary-dark);
        text-decoration: none;
        border-color: var(--primary-light);
        background: var(--surface-muted);
    }

    .section-shell {
        max-width: 1200px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }
    .section-head {
        text-align: center;
        margin-bottom: 24px;
    }
    .section-head h2 {
        font-size: 2rem;
        margin-bottom: 10px;
        color: var(--text);
    }
    .section-head p {
        color: var(--text-muted);
        max-width: 760px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 30px;
        margin-bottom: 0;
    }
    .feature-card {
        --card-color: var(--secondary);
        --card-rgb: var(--secondary-rgb);
        background: var(--surface);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        padding: 30px;
        border-radius: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .feature-card:hover {
        transform: none;
        background: var(--surface-muted);
        border-color: var(--card-color);
    }
    .feature-card:nth-child(2) { --card-color: var(--secondary); --card-rgb: var(--secondary-rgb); }
    .feature-card:nth-child(3) { --card-color: var(--orange-ink); --card-rgb: var(--orange-rgb); }
    .feature-card:nth-child(4) { --card-color: var(--orange-ink); --card-rgb: var(--orange-rgb); }
    .feature-icon {
        width: 70px;
        height: 70px;
        background: rgba(var(--card-rgb), 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 28px;
        color: var(--card-color);
    }

    .timeline-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }
    .timeline-step {
        background: var(--surface);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 22px;
        transition: all 0.3s ease;
        min-height: 200px;
    }
    .timeline-step:hover {
        border-color: rgba(var(--primary-rgb), 0.7);
        transform: none;
    }
    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 12px;
    }
    .timeline-step h3 {
        font-size: 1.1rem;
        margin-bottom: 8px;
        color: var(--text);
    }
    .timeline-step:nth-child(2) .step-number { background: var(--secondary); }
    .timeline-step:nth-child(3) .step-number { background: var(--secondary); }
    .timeline-step:nth-child(4) .step-number { background: var(--primary); color:var(--on-primary); }
    .timeline-step p {
        color: var(--text-muted);
        line-height: 1.55;
        font-size: 0.95rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
    }
    .info-card {
        background: var(--surface);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 24px;
        transition: all 0.3s ease;
    }
    .info-card:hover {
        transform: none;
        border-color: rgba(var(--secondary-rgb), 0.55);
    }
    .info-card h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.08rem;
        margin-bottom: 12px;
        color: var(--text);
    }
    .info-card p {
        color: var(--text-muted);
        line-height: 1.6;
    }
    .landing-list {
        margin: 0;
        padding-left: 18px;
        color: var(--text-muted);
    }
    .landing-list li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .faq-list {
        max-width: 900px;
        margin: 0 auto;
        display: grid;
        gap: 12px;
    }
    .faq-item {
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        background: var(--surface);
        padding: 16px 18px;
    }
    .faq-item summary {
        list-style: none;
        cursor: pointer;
        font-weight: 600;
        color: var(--text);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .faq-item summary::-webkit-details-marker {
        display: none;
    }
    .faq-item p {
        margin-top: 10px;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .cta-band {
        max-width: 1080px;
        margin: 0 auto 50px;
        padding: 28px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.07), rgba(var(--secondary-rgb), 0.05));
        border: 1px solid rgba(var(--primary-rgb), 0.45);
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 20px;
    }
    .cta-band h3 {
        margin-bottom: 8px;
        color: var(--text);
        font-size: 1.35rem;
    }
    .cta-band p {
        color: var(--text-muted);
        margin: 0;
        line-height: 1.6;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.3rem;
        }
        .hero-subtitle {
            font-size: 1rem;
        }
        .section-head h2 {
            font-size: 1.55rem;
        }
        .cta-band {
            grid-template-columns: 1fr;
            text-align: center;
        }
    }
</style>
@endsection

@section('content')
<div class="landing-page">
    <nav class="public-nav" aria-label="Navigasi beranda">
        <a href="{{ route('landing') }}" class="public-brand"><span class="logo-icon"><i class="fas fa-landmark" aria-hidden="true"></i></span><span>PTSP <strong>Online</strong><small>Pelayanan terpadu satu pintu</small></span></a>
        <a href="{{ route('login') }}" class="btn btn-secondary"><i class="fas fa-user-shield" aria-hidden="true"></i> Masuk petugas</a>
    </nav>
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-badge"><span class="hero-badge-dot" aria-hidden="true"></span> Layanan pengadilan, dalam jangkauan</div>
            <h1 class="hero-title">Datang dengan pertanyaan.<br><span>Pulang dengan arahan.</span></h1>
            <p class="hero-subtitle">Konsultasikan kebutuhan layanan pengadilan dari mana saja. Petugas PTSP satuan kerja Anda siap membantu melalui chat dan konsultasi daring.</p>
            <div class="hero-actions"><a href="{{ route('guest.welcome') }}" class="btn btn-primary btn-lg">Mulai konsultasi <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a href="#kanal-layanan" class="btn btn-secondary btn-lg">Lihat kanal layanan</a></div>
            <p class="hero-footnote"><i class="fas fa-landmark" aria-hidden="true"></i> Terhubung ke PTSP sesuai satuan kerja yang Anda pilih.</p>
        </div>
        <aside class="service-desk" aria-label="Cara mengakses PTSP">
            <div class="desk-header"><span class="eyebrow">Satu pintu, banyak cara</span><span class="desk-mark" aria-hidden="true"><i class="fas fa-landmark"></i></span></div>
            <h2>Ruang layanan<br>untuk Anda.</h2>
            <div class="desk-channels"><span><i class="fas fa-comments" aria-hidden="true"></i> Chat</span><span><i class="fas fa-video" aria-hidden="true"></i> Google Meet</span><span><i class="fas fa-microphone-lines" aria-hidden="true"></i> Suara</span></div>
            <ol class="desk-steps"><li><span>1</span><div><strong>Pilih satuan kerja</strong><small>Daftar dengan nomor HP dan data Anda.</small></div></li><li><span>2</span><div><strong>Sampaikan kebutuhan</strong><small>Pilih layanan atau jadwal konsultasi.</small></div></li><li><span>3</span><div><strong>Terhubung dengan petugas</strong><small>Pelayanan oleh PTSP satker Anda.</small></div></li></ol>
        </aside>
    </section>

    <section class="section-shell">
        <div class="section-head">
            <h2 id="kanal-layanan">Pilih cara yang paling nyaman</h2>
            <p>
                PTSP Online dirancang untuk mempercepat komunikasi masyarakat dengan petugas melalui beberapa kanal layanan yang dapat dipilih sesuai kebutuhan.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-comments"></i></div>
                <h3>Live Chat</h3>
                <p style="color: var(--text-muted);">Konsultasi langsung dengan petugas secara real-time melalui layanan pesan teks.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-video"></i></div>
                <h3>Google Meet</h3>
                <p style="color: var(--text-muted);">Tatap muka virtual melalui Google Meet untuk pembahasan layanan yang memerlukan penjelasan mendalam.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-deaf"></i></div>
                <h3>Voice to Text</h3>
                <p style="color: var(--text-muted);">Mendukung komunikasi yang lebih inklusif, termasuk untuk pengguna dengan kebutuhan khusus.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <h3>Call Center</h3>
                <p style="color: var(--text-muted);">Alternatif layanan panggilan bagi pengguna yang memerlukan respon cepat berbasis suara.</p>
            </div>
        </div>
    </section>

    <section class="section-shell">
        <div class="section-head">
            <h2>Alur Layanan PTSP Online</h2>
            <p>
                Proses dibuat sederhana agar pengguna dapat segera terhubung ke petugas tanpa tahapan yang rumit.
            </p>
        </div>

        <div class="timeline-grid">
            <div class="timeline-step">
                <div class="step-number">01</div>
                <h3>Identifikasi Pengunjung</h3>
                <p>Masukkan nomor HP untuk pengecekan data. Jika data belum tersedia, sistem mengarahkan ke pendaftaran singkat.</p>
            </div>
            <div class="timeline-step">
                <div class="step-number">02</div>
                <h3>Pilih Kanal Komunikasi</h3>
                <p>Tentukan metode layanan: chat teks, voice-to-text, meeting virtual, atau call center sesuai kebutuhan layanan.</p>
            </div>
            <div class="timeline-step">
                <div class="step-number">03</div>
                <h3>Konsultasi Dengan Petugas</h3>
                <p>Petugas memandu kebutuhan informasi, verifikasi dokumen awal, serta arahan tindak lanjut administrasi.</p>
            </div>
            <div class="timeline-step">
                <div class="step-number">04</div>
                <h3>Tindak Lanjut Layanan</h3>
                <p>Hasil konsultasi terdokumentasi sehingga pengguna dapat melanjutkan proses layanan dengan lebih terarah.</p>
            </div>
        </div>
    </section>

    <section class="section-shell">
        <div class="section-head">
            <h2>Materi Informasi Layanan</h2>
            <p>
                Ringkasan materi awal ini membantu pengguna menyiapkan data dan memahami standar pelayanan sebelum memulai konsultasi.
            </p>
        </div>

        <div class="info-grid">
            <article class="info-card">
                <h3><i class="fas fa-folder-open"></i> Jenis Layanan</h3>
                <ul class="landing-list">
                    <li>Informasi pendaftaran dan administrasi perkara.</li>
                    <li>Konsultasi persyaratan layanan pengadilan.</li>
                    <li>Koordinasi jadwal pembahasan melalui meeting online.</li>
                    <li>Pendampingan awal pengajuan layanan digital.</li>
                </ul>
            </article>

            <article class="info-card">
                <h3><i class="fas fa-file-circle-check"></i> Dokumen Awal Yang Umum Dibutuhkan</h3>
                <ul class="landing-list">
                    <li>Identitas pemohon sesuai kebutuhan layanan.</li>
                    <li>Nomor kontak aktif untuk verifikasi layanan.</li>
                    <li>Dokumen pendukung sesuai jenis layanan yang diajukan.</li>
                    <li>Alamat email aktif untuk notifikasi lanjutan.</li>
                </ul>
            </article>

            <article class="info-card">
                <h3><i class="fas fa-clock"></i> Standar Pelayanan</h3>
                <p>
                    Verifikasi awal konsultasi ditargetkan pada hari kerja. Pengguna dapat memantau proses komunikasi secara real-time melalui dashboard, riwayat chat, dan jadwal meeting yang tersimpan dalam sistem.
                </p>
            </article>
        </div>
    </section>

    <section class="section-shell">
        <div class="section-head">
            <h2>Pertanyaan Umum</h2>
        </div>

        <div class="faq-list">
            <details class="faq-item" open>
                <summary>Apakah saya harus membuat akun terlebih dahulu? <i class="fas fa-plus"></i></summary>
                <p>Untuk pengunjung, proses dimulai dari nomor HP. Jika data belum terdaftar, sistem akan membuatkan akun pengunjung secara otomatis setelah Anda melengkapi nama.</p>
            </details>
            <details class="faq-item">
                <summary>Kapan sebaiknya menggunakan meeting online? <i class="fas fa-plus"></i></summary>
                <p>Meeting online cocok untuk pembahasan layanan yang membutuhkan penjelasan detail, review dokumen, atau koordinasi langsung dengan petugas terkait.</p>
            </details>
            <details class="faq-item">
                <summary>Apakah riwayat konsultasi saya tersimpan? <i class="fas fa-plus"></i></summary>
                <p>Ya, percakapan dan aktivitas layanan tersimpan pada sistem sehingga memudahkan tindak lanjut proses administrasi berikutnya.</p>
            </details>
            <details class="faq-item">
                <summary>Bagaimana jika saya membutuhkan bantuan cepat? <i class="fas fa-plus"></i></summary>
                <p>Anda dapat memilih kanal call center atau live chat untuk mendapatkan respon lebih cepat dari petugas yang sedang online.</p>
            </details>
        </div>
    </section>

    <section class="cta-band">
        <div>
            <h3>Mulai Layanan Digital Anda Sekarang</h3>
            <p>Masuk sebagai pengunjung untuk berkonsultasi langsung dengan petugas PTSP melalui kanal layanan yang paling sesuai dengan kebutuhan Anda.</p>
        </div>
        <a href="{{ route('guest.welcome') }}" class="btn btn-lg-custom">
            Mulai dari Nomor HP <i class="fas fa-arrow-right"></i>
        </a>
    </section>

    <footer style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px;">
        &copy; {{ date('Y') }} PTSP Online. Transparan, inklusif, dan responsif untuk pelayanan publik.
    </footer>
</div>
@endsection

