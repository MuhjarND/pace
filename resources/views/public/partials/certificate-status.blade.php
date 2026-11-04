@if($certificate['state'] !== 'not_applicable')
<section class="citizen-certificate" aria-labelledby="certificateHeading">
    <div class="citizen-certificate-heading"><div><p class="eyebrow">Akta cerai</p><h3 id="certificateHeading">Penerbitan dan pengambilan</h3></div><i class="fas fa-file-circle-check" aria-hidden="true"></i></div>
    @if($certificate['state']==='unavailable')
        <p class="citizen-certificate-state">Informasi akta cerai belum tersedia</p>
        <p>Data akta belum tersinkron atau belum dapat dimuat. Status perkara di atas tetap dapat dilihat; PTSP dapat membantu mengonfirmasi akta Anda.</p>
    @else
        <p class="citizen-certificate-state">{{ ['issued'=>'Akta terbit tercatat','incomplete'=>'Data akta belum lengkap','not_recorded'=>'Akta belum tercatat pada data tersinkron'][$certificate['state']] }}</p>
        @if($certificate['issued_on'])<p class="field-hint">Tanggal terbit tercatat: {{ \Carbon\Carbon::parse($certificate['issued_on'])->format('d/m/Y') }}</p>@endif
        @if($certificate['collections'])
        <div class="citizen-collection-grid">
            @foreach(['party1'=>'Pihak 1','party2'=>'Pihak 2'] as $key=>$label)
            @php($collection=$certificate['collections'][$key])
            <div class="citizen-collection"><h4>{{ $label }}</h4>
                <p class="citizen-collection-status {{ $collection['state']==='collected' ? 'is-collected' : '' }}"><i class="fas {{ $collection['state']==='collected' ? 'fa-circle-check' : 'fa-circle-info' }}" aria-hidden="true"></i><strong>{{ ['collected'=>'Sudah diambil (tercatat)','not_recorded'=>'Pengambilan belum tercatat','unavailable'=>'Informasi pengambilan belum tersedia'][$collection['state']] }}</strong></p>
                @if($collection['collected_on'])<p class="field-hint">Tanggal penyerahan: {{ \Carbon\Carbon::parse($collection['collected_on'])->format('d/m/Y') }}</p>@endif
            </div>
            @endforeach
        </div>
        @endif
        <p class="field-hint citizen-certificate-freshness">Sinkronisasi akta cerai: {{ $certificate['synced_at'] }}</p>
    @endif
    <p class="field-hint citizen-certificate-note">Catatan pengambilan mengikuti penyerahan yang dicatat di SIPP satker, bukan status unduhan dokumen elektronik. Akta terbit tidak otomatis berarti siap diambil. Konfirmasikan layanan kepada PTSP.</p>
</section>
@endif
