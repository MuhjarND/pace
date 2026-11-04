@php
    $chosen = isset($selectedRegion) && $selectedRegion ? \App\Region::find($selectedRegion) : null;
    $codes = $chosen ? array_reverse($chosen->ancestors()) : [];
    $pickerId = $pickerId ?? 'area';
    $fieldName = $fieldName ?? 'region_code';
@endphp
<div class="region-picker" data-region-picker data-endpoint="{{ route('public.regions') }}">
<input type="hidden" name="{{ $fieldName }}" value="{{ $chosen ? $chosen->code : '' }}" data-region-value>
<div class="citizen-field-grid">
@foreach(['Provinsi','Kabupaten / kota','Distrik','Kampung / kelurahan'] as $i=>$label)
@php($options = $i === 0 ? \App\Region::whereNull('parent_code')->orderBy('name')->get() : (isset($codes[$i-1]) ? \App\Region::where('parent_code',$codes[$i-1])->orderBy('name')->get() : collect()))
<div class="form-group"><label for="{{ $pickerId }}-{{ $i }}" class="form-label">{{ $label }}</label><select id="{{ $pickerId }}-{{ $i }}" class="form-control" data-region-level="{{ $i }}" {{ !empty($requireProvince) && $i === 0 ? 'required' : '' }} {{ $i>0 && !isset($codes[$i-1]) ? 'disabled' : '' }}><option value="">{{ ['Pilih provinsi','Pilih kabupaten / kota','Pilih distrik','Pilih kampung / kelurahan'][$i] }}</option>@foreach($options as $option)<option value="{{ $option->code }}" {{ ($codes[$i]??'') === $option->code ? 'selected' : '' }}>{{ $option->name }}</option>@endforeach</select></div>
@endforeach</div><p class="field-hint region-feedback" role="status"></p><button type="button" class="btn btn-secondary btn-sm" data-region-retry hidden>Coba muat wilayah</button>
</div>
