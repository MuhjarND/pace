@extends('layouts.public')
@section('title','Cek status perkara dan akta cerai')
@section('content')
@include('public.partials.page-heading', ['heading'=>'Status perkara & akta cerai', 'description'=>'Periksa perkembangan perkara dan akta cerai dalam satu pencarian.', 'icon'=>'far fa-file-lines','illustration'=>'case-files'])
<div class="citizen-columns pace-form-layout pace-case-layout">
    <section class="citizen-panel" aria-labelledby="caseFormHeading">
        <h2 id="caseFormHeading">Cari perkara Anda</h2>
        <p class="citizen-description">Siapkan nomor perkara lengkap dari bukti pendaftaran Anda.</p>
        <form method="POST" action="{{ route('public.cases.lookup') }}#hasil" data-busy-message="Memeriksa status perkara dan akta cerai…" novalidate>
            @csrf
            <div class="form-group"><label for="caseCourt" class="form-label">Pengadilan yang menangani</label>
                <select id="caseCourt" name="work_unit_id" class="form-control" required><option value="">Pilih pengadilan</option>
                    @foreach($units as $item)<option value="{{ $item->id }}" {{ (int)old('work_unit_id',optional($unit)->id)===(int)$item->id?'selected':'' }}>{{ $item->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label for="caseNumber" class="form-label">Nomor perkara</label>
                <div class="citizen-search-field"><input type="search" id="caseNumber" name="case_number" class="form-control" data-clear-search required maxlength="100" autocomplete="off" spellcheck="false" placeholder="123/Pdt.G/2026/PA.Srg" value="{{ old('case_number',$number) }}" aria-describedby="caseNumberHint"></div>
                <p class="field-hint" id="caseNumberHint">Masukkan nomor, jenis, tahun, dan kode pengadilan. Contoh: 123/Pdt.G/2026/PA.Srg.</p>
            </div>
            <button type="submit" class="btn btn-primary citizen-primary">Cek status <i class="fas fa-magnifying-glass" aria-hidden="true"></i></button>
            <p class="field-hint citizen-submit-status" data-form-status role="status"></p>
            <p class="field-hint citizen-source">Hasil berupa ringkasan status; tidak menampilkan identitas atau dokumen para pihak.</p>
        </form>
    </section>
    <aside class="citizen-panel citizen-guide pace-case-guide"><h2>Informasi yang dapat dilihat</h2>
<div class="pace-info-row"><i class="fas fa-scale-balanced" aria-hidden="true"></i><div><h3>Status perkara</h3><p>Tahapan dan perkembangan terakhir.</p></div></div>
<div class="pace-info-row"><i class="far fa-file-lines" aria-hidden="true"></i><div><h3>Akta cerai</h3><p>Penerbitan dan catatan pengambilan akta.</p></div></div>
<div class="pace-aside-divider"><h3>Perlu penjelasan?</h3><p>Petugas pengadilan terkait dapat membantu menjelaskan status dan langkah berikutnya.</p><a href="{{ route('public.consult',array_filter(['unit'=>optional($unit)->id])) }}">Tanyakan kepada PTSP <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    </aside>
</div>
@if($result)
<section class="citizen-panel citizen-result citizen-case-result" id="hasil" tabindex="-1" aria-labelledby="caseResultHeading">
    <p class="eyebrow">Hasil pengecekan</p>
    @if($result['state']==='found')
        <h2 id="caseResultHeading">{{ $result['case']['number'] }}</h2><p class="citizen-description">{{ $unit->name }}</p>
        <div class="citizen-case-stage"><span class="field-hint">Tahapan terakhir yang tercatat</span><strong>{{ $result['case']['stage'] ?: 'Tahapan belum tersedia' }}</strong><p>{{ $result['case']['process'] ?: 'Rincian proses terakhir belum tersedia.' }}</p></div>
        <dl class="citizen-details">
            <div><dt>Jenis perkara</dt><dd>{{ $result['case']['type'] ?: 'Belum tersedia' }}</dd></div>
            <div><dt>Tanggal daftar</dt><dd>{{ $result['case']['registered_on'] ? \Carbon\Carbon::parse($result['case']['registered_on'])->format('d/m/Y') : 'Belum tersedia' }}</dd></div>
            <div><dt>Tanggal putusan</dt><dd>{{ $result['case']['decided_on'] ? \Carbon\Carbon::parse($result['case']['decided_on'])->format('d/m/Y') : 'Belum tercatat pada data yang tersedia' }}</dd></div>
        </dl>
        @include('public.partials.certificate-status', ['certificate'=>$result['certificate']])
    @elseif($result['state']==='not_found')
        <h2 id="caseResultHeading">Perkara belum ditemukan</h2><p>Nomor <strong>{{ $number }}</strong> belum ditemukan pada data tersinkron {{ $unit->name }}.</p><p class="citizen-description">Periksa kembali pengadilan dan nomor perkara. Perkara yang baru didaftarkan mungkin belum tersinkron; PTSP dapat membantu memastikannya.</p>
    @elseif($result['state']==='rate_limited')
        <h2 id="caseResultHeading">Tunggu sebentar sebelum mengecek lagi</h2><p class="citizen-description">Batas pengecekan sementara telah tercapai. Coba kembali setelah satu menit; isian Anda tetap tersedia di atas.</p>
    @else
        <h2 id="caseResultHeading">Status belum dapat dimuat</h2><p class="citizen-description">Sumber data sedang tidak tersedia atau data pengadilan belum siap. Isian Anda tetap tersedia; gunakan tombol Cek status untuk mencoba lagi atau hubungi PTSP.</p>
    @endif
    @if($result['state']!=='rate_limited')<p class="field-hint citizen-case-freshness"><i class="fas fa-clock" aria-hidden="true"></i> {{ $result['synced_at'] ? 'Sinkronisasi satker: '.$result['synced_at'] : 'Waktu sinkronisasi satker belum tersedia.' }} · Sumber: Kasuari</p>@endif
    @if($unit)<div class="citizen-actions"><a class="btn btn-primary" href="{{ route('public.consult',['unit'=>$unit->id,'case_number'=>\App\Services\CaseStatusLookup::normalize($number)]) }}">Tanyakan kepada PTSP {{ preg_replace('/^Pengadilan Agama /','',$unit->name) }}</a><a class="btn btn-secondary" href="#caseCourt">Periksa kembali isian</a></div>@endif
</section>
@else
<div class="citizen-support"><div><h2>Belum tahu nomor perkara?</h2><p>Lihat bukti pendaftaran atau minta bantuan petugas pengadilan yang menangani.</p></div><a href="{{ route('public.consult',array_filter(['unit'=>optional($unit)->id])) }}">Konsultasi dengan PTSP <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
@endif
@endsection
