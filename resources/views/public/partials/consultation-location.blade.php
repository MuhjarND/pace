<section class="consultation-location" aria-labelledby="originHeading">
    @if(empty($compactLocation))<h2 id="originHeading">Wilayah domisili (opsional)</h2>@endif
    <p class="field-hint">Pilih domisili hingga kampung / kelurahan untuk menghitung estimasi perjalanan ke pengadilan tujuan. Perhitungan memakai titik acuan wilayah domisili.</p>
    @include('public.partials.region-picker',['selectedRegion'=>old('origin_region_code',optional($region)->level === 4 ? $region->code : null),'fieldName'=>'origin_region_code','pickerId'=>'domicile'])
    <noscript><p class="field-hint">Aktifkan JavaScript untuk memilih wilayah bertingkat. Anda tetap dapat mengajukan konsultasi tanpa domisili.</p></noscript>
</section>