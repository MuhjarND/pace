@php
$services = [
    'public.finder'=>['label'=>'Cari pengadilan','header'=>'Cari pengadilan','icon'=>'fa-location-dot','description'=>'Temukan pengadilan di wilayah Anda'],
    'public.fees'=>['label'=>'Perkiraan biaya','header'=>'Perkiraan biaya','icon'=>'fa-calculator','scene'=>'fees','description'=>'Lihat estimasi biaya layanan'],
    'public.cases'=>['label'=>'Cek status perkara dan akta cerai','short'=>'Perkara & akta cerai','header'=>'Perkara & akta cerai','icon'=>'fa-file-lines','family'=>'far','scene'=>'case-files','description'=>'Pantau perkembangan perkara Anda'],
    'public.consult'=>['label'=>'Konsultasi PTSP','header'=>'Konsultasi PTSP','icon'=>'fa-comment-dots','family'=>'far','scene'=>'consultation','description'=>'Tanya seputar layanan dan persyaratan'],
    'public.complaints.create'=>['label'=>'Pengaduan','header'=>'Pengaduan','icon'=>'fa-bullhorn','scene'=>'privacy','description'=>'Sampaikan pengaduan layanan dan gratifikasi'],
];
@endphp
@foreach($services as $name=>$service)
@if(!empty($excludeFinder) && $name === 'public.finder') @continue @endif
@php
    $isActive = request()->routeIs($name) || ($name === 'public.finder' && request()->routeIs('landing')) || ($name === 'public.cases' && request()->routeIs('public.cases.*')) || ($name === 'public.complaints.create' && request()->routeIs('public.complaints.*'));
    $context = !empty($preserveContext) ? array_filter(['region'=>isset($region) && $region ? $region->code : null,'unit'=>isset($unit) && $unit ? $unit->id : null]) : [];
@endphp
<a href="{{ route($name,$context) }}" class="citizen-service-link {{ $isActive ? 'active' : '' }}" aria-label="{{ $name === 'public.complaints.create' ? 'Pengaduan layanan dan gratifikasi' : $service['label'] }}" @if($isActive) aria-current="page" @endif>
@if(!empty($withIllustrations) && isset($service['scene']))
<span class="citizen-service-art" aria-hidden="true">@include('public.partials.illustration',['scene'=>$service['scene'],'priority'=>false])</span>
<span class="citizen-service-art-label"><strong>{{ $service['short'] ?? $service['label'] }}</strong><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
@elseif(!empty($withDescriptions))
<span class="citizen-service-icon {{ isset($service['family']) ? 'citizen-service-icon-outline' : '' }}" aria-hidden="true"><i class="{{ $service['family'] ?? 'fas' }} {{ $service['icon'] }}"></i></span>
<span class="citizen-service-copy"><strong>{{ $service['label'] }}</strong><span>{{ $service['description'] }}</span></span>
@else{{ $service['header'] }}@endif
</a>
@endforeach
