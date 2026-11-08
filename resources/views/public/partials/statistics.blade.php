@php
    $stats = $statistics;
    $estimate = $stats['estimate'];
    $demo = $stats['data_mode'] === 'demo';
    $number = function ($value, $decimals = 0) { return number_format($value, $decimals, ',', '.'); };
    $percentage = function ($value) use ($stats) { return $stats['total'] ? $value / $stats['total'] * 100 : 0; };
    $percent = function ($value) use ($percentage, $number) { return $number($percentage($value), 1); };
    $width = function ($value, $max) { return number_format($value / max(1, $max) * 100, 5, '.', ''); };
    $channelLabels = ['meet'=>'Google Meet', 'chat'=>'Chat PTSP', 'mace'=>'Mace (offline)'];
    $statusLabels = ['completed'=>'Selesai', 'active'=>'Aktif', 'cancelled'=>'Dibatalkan'];
    $meetEnd = $percentage($stats['channels']['meet']);
    $chatEnd = $meetEnd + $percentage($stats['channels']['chat']);
    $completion = $percentage($stats['statuses']['completed']);
    $statsRoute = request()->routeIs('landing') ? 'landing' : 'public.finder';
    $chartType = request('stats_chart','channels');
    $chartLabels = $chartType === 'statuses' ? $statusLabels : $channelLabels;
    $statsQuery = array_filter(['region'=>optional($region)->code,'stats_unit'=>optional($stats['selected_unit'])->id,
        'stats_mode'=>$stats['data_mode'],'stats_chart'=>$chartType]);
@endphp
<section class="public-statistics" id="statistik" aria-labelledby="statisticsHeading">
    <header class="stats-heading">
        <div class="stats-heading-copy"><div class="stats-title-row"><h2 id="statisticsHeading">Statistik layanan</h2>@if($demo)<span class="stats-demo-badge">Data contoh</span>@endif</div><p>Gambaran pelayanan dan perjalanan masyarakat.</p></div>
        <div class="stats-controls">
            <nav class="stats-source-tabs" aria-label="Sumber statistik">
                @foreach(['live'=>'Data layanan','demo'=>'Data contoh'] as $mode=>$label)<a href="{{ route($statsRoute,array_merge($statsQuery,['stats_mode'=>$mode])) }}#statistik" @if($stats['data_mode']===$mode)aria-current="page"@endif>{{ $label }}</a>@endforeach
            </nav>
            <form method="GET" action="{{ route($statsRoute) }}#statistik" class="stats-filter">
                @if($region)<input type="hidden" name="region" value="{{ $region->code }}">@endif
                <input type="hidden" name="stats_mode" value="{{ $stats['data_mode'] }}"><input type="hidden" name="stats_chart" value="{{ $chartType }}">
                <label for="statisticsUnit" class="sr-only">Satker statistik</label>
                <select name="stats_unit" id="statisticsUnit" class="form-control"><option value="">Semua satker</option>@foreach($stats['units'] as $statsUnit)<option value="{{ $statsUnit->id }}" {{ optional($stats['selected_unit'])->id === $statsUnit->id ? 'selected' : '' }}>{{ $statsUnit->name }}</option>@endforeach</select>
                <button class="btn btn-primary" type="submit">Tampilkan</button><span>Seluruh periode · WIT</span>
            </form>
        </div>
    </header>
    <div class="stats-kpis">
        <article class="stats-kpi stats-kpi-primary"><span class="stats-kpi-icon"><i class="fas fa-users" aria-hidden="true"></i></span><h3>Total konsultasi</h3><strong>{{ $number($stats['total']) }}</strong><p>{{ $number($stats['served_units']) }} satker · {{ $number($stats['statuses']['completed']) }} konsultasi selesai</p></article>
        <article class="stats-kpi"><span class="stats-kpi-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span><h3>Jarak perjalanan (PP)</h3><strong>{{ $estimate['distance_km'] !== null ? $number($estimate['distance_km'], $demo ? 0 : 2) : '—' }} @if($estimate['distance_km'] !== null)<small>km</small>@endif</strong><p>@if($estimate['distance_km'] !== null)Rute domisili ke pengadilan @else Data estimasi belum tersedia. @endif</p></article>
        <article class="stats-kpi"><span class="stats-kpi-icon"><i class="far fa-clock" aria-hidden="true"></i></span><h3>Waktu perjalanan (PP)</h3><strong class="stats-time">@if($estimate['minutes'] !== null){{ $number(intdiv($estimate['minutes'],60)) }} <small>jam</small> {{ $estimate['minutes'] % 60 }} <small>mnt</small>@else — @endif</strong><p>@if($estimate['minutes'] !== null)Akumulasi durasi perjalanan @else Data estimasi belum tersedia. @endif</p></article>
        <article class="stats-kpi"><span class="stats-kpi-icon"><i class="fas fa-gas-pump" aria-hidden="true"></i></span><h3>Estimasi biaya BBM (PP)</h3><strong class="stats-money">{{ $estimate['fuel_cost'] !== null ? 'Rp '.$number($estimate['fuel_cost']) : '—' }}</strong><p>@if($estimate['fuel_cost'] !== null)Berdasarkan jarak rute dan acuan BBM @else Data estimasi belum tersedia. @endif</p></article>
    </div>
    @if(!$stats['total'])<p class="stats-empty" role="status">{{ $demo ? 'Belum ada data contoh untuk satker ini.' : 'Belum ada konsultasi yang tercatat'.($stats['selected_unit'] ? ' pada satker ini.' : '.') }}</p>@endif
    <div class="stats-overview">
        <article class="stats-panel stats-channel-panel"><h3>Kanal konsultasi</h3><div class="stats-channel-content">
            <div class="stats-donut {{ !$stats['total'] ? 'stats-donut-empty' : '' }}" aria-hidden="true" style="--meet-end:{{ number_format($meetEnd,5,'.','') }}%;--chat-end:{{ number_format($chatEnd,5,'.','') }}%"><div><strong>{{ $number($stats['total']) }}</strong><span>Konsultasi</span></div></div>
            <ul class="stats-channel-list">@foreach($channelLabels as $key=>$label)<li><span class="stats-color-{{ $key }}" aria-hidden="true"></span><span>{{ $label }}</span><strong>{{ $number($stats['channels'][$key]) }} <small>· {{ $percent($stats['channels'][$key]) }}%</small></strong></li>@endforeach</ul>
        </div></article>
        <article class="stats-panel stats-completion-panel"><h3>Penyelesaian layanan</h3>
            <p class="stats-completion"><strong>{{ $number($completion, $completion == floor($completion) ? 0 : 1) }}%</strong><span>konsultasi selesai</span></p>
            <div class="stats-progress" aria-hidden="true">@foreach($statusLabels as $key=>$label)<span class="stats-color-{{ $key }}" style="width:{{ $width($stats['statuses'][$key],$stats['total']) }}%">@if($percentage($stats['statuses'][$key])>=8){{ $number($percentage($stats['statuses'][$key]), $key==='completed' ? 0 : 1) }}%@endif</span>@endforeach</div>
            <dl class="stats-status-counts">@foreach($statusLabels as $key=>$label)<div><dt>{{ $label }}</dt><dd>{{ $number($stats['statuses'][$key]) }}<span class="sr-only"> · {{ $percent($stats['statuses'][$key]) }}%</span></dd></div>@endforeach</dl>
            <p class="stats-panel-note">Termasuk konsultasi yang sedang mengantre.</p>
        </article>
    </div>
    <div class="stats-detail-grid">
        <article class="stats-panel stats-courts-panel"><header class="stats-panel-heading"><h3>Layanan per satker</h3><nav class="stats-chart-tabs" aria-label="Jenis grafik satker">@foreach(['channels'=>'Kanal','statuses'=>'Status'] as $type=>$label)<a href="{{ route($statsRoute,array_merge($statsQuery,['stats_chart'=>$type])) }}#statistik" @if($chartType===$type)aria-current="page"@endif>{{ $label }}</a>@endforeach</nav></header>
            <ul class="stats-bars" aria-label="{{ $chartType==='statuses' ? 'Status per satker' : 'Kanal per satker' }}">@foreach($stats['by_unit'] as $row)<li><span class="stats-bar-label" title="{{ $row['name'] }}">{{ $row['short'] }}</span><div class="stats-bar-track" aria-hidden="true">@foreach($chartLabels as $key=>$label)<span class="stats-color-{{ $key }}" style="width:{{ $width($row[$chartType][$key],$stats['max_unit_total']) }}%">@if($row[$chartType][$key] && $row[$chartType][$key]/$stats['max_unit_total']>=0.055)<span>{{ $number($row[$chartType][$key]) }}</span>@endif</span>@endforeach</div><strong>{{ $number($row['total']) }}</strong><span class="sr-only">@foreach($chartLabels as $key=>$label){{ $label }}: {{ $number($row[$chartType][$key]) }}. @endforeach</span></li>@endforeach</ul>
            <div class="stats-axis" aria-hidden="true"><span></span><div>@for($tick=0;$tick<=6;$tick++)<span>{{ $number($stats['max_unit_total']*$tick/6,$stats['max_unit_total'] % 6 === 0 ? 0 : 1) }}</span>@endfor</div><span></span></div>
            <ul class="stats-legend">@foreach($chartLabels as $key=>$label)<li><span class="stats-color-{{ $key }}" aria-hidden="true"></span>{{ $label }}</li>@endforeach</ul>
        </article>
        <article class="stats-panel stats-purpose-panel"><h3>Keperluan konsultasi</h3>
            @if(count($stats['purposes']))<ul class="stats-purpose-bars">@foreach($stats['purposes'] as $label=>$count)<li><span>{{ $label }}</span><div aria-hidden="true"><span style="width:{{ $width($count,$stats['max_purpose_total']) }}%"></span></div><strong>{{ $number($count) }}</strong></li>@endforeach</ul>@else<p class="stats-panel-note">Belum ada data keperluan.</p>@endif
        </article>
    </div>
    <p class="stats-source">@if($demo)Data contoh untuk pratinjau tampilan. Angka perjalanan merupakan simulasi.@else Jumlah konsultasi, bukan orang unik. Rute PP domisili–pengadilan tersedia untuk {{ $number($estimate['route_count']) }} dari {{ $number($stats['total']) }} konsultasi; BBM untuk {{ $number($estimate['fuel_count']) }} konsultasi sesuai acuan satker. @endif</p>
</section>