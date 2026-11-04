<div class="operations-stats">
@foreach([['waiting','Menunggu','users','Pemohon dalam antrean'],['serving','Sedang dilayani','user','Sedang dalam konsultasi'],['pending','Perlu verifikasi','file-circle-check','Menunggu verifikasi petugas'],['completed','Selesai hari ini','circle-check','Konsultasi selesai hari ini']] as $stat)
<div class="glass-card operations-stat"><div class="operations-stat-icon {{ $stat[0] === 'pending' ? 'operations-stat-orange' : '' }}"><i class="fas fa-{{ $stat[2] }}" aria-hidden="true"></i></div><div><h2>{{ $stat[1] }}</h2><strong>{{ $summary[$stat[0]] }}</strong><p>{{ $stat[3] }}</p></div></div>
@endforeach</div>
<div class="operations-queue-layout"><section class="glass-card operations-card operations-queue-main" aria-label="Daftar antrean konsultasi">
<nav class="operations-tabs" aria-label="Tampilan antrean">@foreach(['active'=>'Antrean aktif','pending'=>'Perlu verifikasi','history'=>'Riwayat'] as $key=>$label)<a href="{{ route('operations.queue', ['tab'=>$key, 'work_unit'=>$unit->name, 'date'=>$date]) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key)aria-current="page"@endif>{{ $label }}</a>@endforeach</nav>
@if($tab === 'history')<form method="GET" action="{{ route('operations.queue') }}" class="operations-history-filter"><input type="hidden" name="tab" value="history"><input type="hidden" name="work_unit" value="{{ $unit->name }}"><div><label for="historyDate" class="form-label">Tanggal jadwal (WIT)</label><input id="historyDate" class="form-control" type="date" name="date" value="{{ $date }}" required></div><button class="btn btn-secondary" type="submit">Tampilkan riwayat</button></form>@endif
<div class="operations-table-wrap" tabindex="0" role="region" aria-label="{{ $tab === 'pending' ? 'Konsultasi menunggu verifikasi' : ($tab === 'history' ? 'Riwayat konsultasi' : 'Antrean aktif') }}"><table class="data-table operations-table queue-operations-table {{ $rows->isEmpty() ? 'queue-is-empty' : '' }}"><thead><tr><th scope="col">Nomor</th><th scope="col">Pemohon / topik</th><th scope="col">Jadwal WIT</th><th scope="col">{{ $tab === 'active' ? 'Waktu tunggu' : 'Verifikasi' }}</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead><tbody>
@forelse($rows as $meeting)
<tr><td><strong class="queue-number">{{ $meeting->queueLabel() }}</strong></td><td><strong>{{ optional($meeting->user)->name ?? 'Pengguna' }}</strong>@if($meeting->guestTicket)<span class="cell-detail">Tamu · WhatsApp berakhir {{ substr($meeting->guestTicket->phone ?? '',-4) }}</span>@endif<span class="cell-detail">{{ $meeting->topic }}</span></td><td><time datetime="{{ $meeting->start_time->toIso8601String() }}">{{ $meeting->localStart()->format('d/m/Y') }}<span class="cell-detail">{{ $meeting->localStart()->format('H:i') }} WIT</span></time></td><td>
@if($tab === 'active')@if($meeting->status === 'started'){{ $meeting->started_at ? $meeting->started_at->diffInMinutes(now()).' menit' : 'Belum tercatat' }}<span class="cell-detail">Lama konsultasi</span>@else{{ max(0, $meeting->start_time->diffInMinutes(now(), false)) }} menit<span class="cell-detail">Sejak jadwal</span>@endif
@else{{ $meeting->verificationLabel() }}@endif
</td><td><span class="badge {{ $meeting->status === 'started' || $meeting->status === 'ended' ? 'badge-success' : ($meeting->status === 'cancelled' ? 'badge-neutral' : 'badge-warning') }}">{{ $meeting->statusLabel() }}</span></td><td><div class="operations-row-actions">
@if($canManage && $tab === 'pending')
<form method="POST" action="{{ route('meetings.verify', $meeting) }}">@csrf<input type="hidden" name="queue_return" value="1"><input type="hidden" name="decision" value="verified"><button class="btn btn-primary btn-sm" type="submit" aria-label="Verifikasi antrean {{ $meeting->queueLabel() }}">Verifikasi</button></form>
<form method="POST" action="{{ route('meetings.verify', $meeting) }}" data-confirm-title="Tolak pengajuan konsultasi?" data-confirm-description="Pengajuan ini akan dibatalkan dan kuotanya dilepaskan. Riwayat keputusan tetap tersimpan." data-confirm-action="Tolak pengajuan">@csrf<input type="hidden" name="queue_return" value="1"><input type="hidden" name="decision" value="rejected"><button class="btn btn-secondary btn-sm" type="submit" aria-label="Tolak antrean {{ $meeting->queueLabel() }}">Tolak</button></form>
@elseif($canManage && $tab === 'active' && (int)$meeting->id === (int)$headId)
@if($meeting->guestTicket && !$meeting->called_at)
<form method="POST" action="{{ route('meetings.call-guest',$meeting) }}">@csrf<button type="submit" class="btn btn-primary btn-sm">Panggil tamu</button></form>
@else
<form method="POST" action="{{ $meeting->status === 'started' ? route('meetings.complete', $meeting) : route('meetings.start', $meeting) }}">@csrf<input type="hidden" name="queue_return" value="1"><button class="btn btn-primary btn-sm" type="submit">{{ $meeting->status === 'started' ? 'Selesai' : 'Mulai layani' }}</button></form><a class="btn btn-secondary btn-sm" href="{{ route('meetings.join', $meeting) }}" target="_blank" rel="noopener">Buka Meet <span class="sr-only">untuk {{ $meeting->queueLabel() }} di tab baru</span></a>
@endif
@if($meeting->guestTicket && $meeting->guestTicket->notification)
@php($notice=$meeting->guestTicket->notification)
<span class="cell-detail">WA: {{ $notice->statusLabel() }}</span>
@if($notice->last_error)<span class="cell-detail">{{ $notice->last_error }}</span>@endif
@if(in_array($notice->status,['failed','blocked']) && !$notice->provider_id && $notice->attempts<3)
<form method="POST" action="{{ route('meetings.retry-wa',$meeting) }}">@csrf<button class="btn btn-secondary btn-sm" type="submit">Coba kirim WhatsApp</button></form>
@endif
@endif
<form method="POST" action="{{ route('meetings.destroy',$meeting) }}" data-confirm-title="Batalkan antrean ini?" data-confirm-description="Konsultasi dibatalkan dan undangan tidak dapat dipakai lagi." data-confirm-action="Batalkan konsultasi">@csrf @method('DELETE')<input type="hidden" name="queue_return" value="1"><button type="submit" class="btn btn-secondary btn-sm">Batalkan</button></form>
@else<span class="cell-detail">{{ !$canManage ? 'Baca saja' : ($tab === 'active' ? 'Menunggu giliran' : '—') }}</span>@endif
</div></td></tr>
@empty<tr><td colspan="6"><div class="operations-queue-empty"><div class="operations-empty-icon"><i class="fas fa-{{ $tab === 'history' ? 'clock-rotate-left' : 'users' }}" aria-hidden="true"></i></div><h2>{{ $tab === 'active' ? 'Belum ada antrean aktif' : ($tab === 'pending' ? 'Tidak ada pengajuan menunggu verifikasi' : 'Belum ada riwayat pada tanggal ini') }}</h2><p>{{ $tab === 'active' ? 'Saat ini belum ada pemohon yang sedang mengantre untuk satker yang dipilih.' : ($tab === 'pending' ? 'Pengajuan jadwal mendatang akan muncul di sini untuk ditinjau petugas.' : 'Pilih tanggal jadwal lain untuk melihat konsultasi selesai atau dibatalkan.') }}</p></div></td></tr>@endforelse
</tbody></table></div>@include('operations.partials.pagination', ['paginator'=>$rows])
</section><aside class="glass-card operations-card operations-aside queue-hours-aside"><div class="operations-aside-heading"><div class="operations-aside-icon"><i class="fas fa-clock" aria-hidden="true"></i></div><h2>Jam layanan satker</h2></div>
<div class="operations-hours-summary">
@if(!$unit->is_active)<strong>Penerimaan baru nonaktif</strong><p>Riwayat dan konsultasi yang sudah terdaftar tetap dapat dilayani.</p>
@elseif(!$unit->schedule_enabled)<i class="far fa-clock" aria-hidden="true"></i><strong>Belum diterapkan</strong><p>Penjadwalan baru belum dibatasi oleh jam layanan. Atur dan terapkan jadwal sesuai operasional satker.</p>
@else
@php($todayHours = $unit->hours->firstWhere('weekday', $now->dayOfWeek))
@php($holiday = $unit->holidays->firstWhere('date', $now->toDateString()))
<strong>{{ $holiday ? 'Tutup: '.$holiday->name : ($todayHours && $todayHours->is_open ? $todayHours->opens_at.'–'.$todayHours->closes_at.' WIT' : 'Hari ini tutup') }}</strong><p>Jadwal hari ini · {{ $now->format('d/m/Y') }}</p>@if(!$holiday && $todayHours && $todayHours->is_open && $todayHours->break_start)<p>Istirahat {{ $todayHours->break_start }}–{{ $todayHours->break_end }} WIT.</p>@endif<p>{{ $unit->daily_quota !== null ? 'Kuota '.$unit->daily_quota.' konsultasi per hari.' : 'Tanpa batas kuota harian.' }}</p>
@endif</div>
@if($canManage)<a href="{{ route('operations.hours') }}" class="btn btn-primary"><i class="fas fa-gear" aria-hidden="true"></i> Atur jam layanan</a>@endif<a href="{{ route('meetings.index') }}" class="btn btn-secondary">Semua jadwal konsultasi</a><p class="field-hint">Antrean aktif mencakup jadwal yang sudah waktunya, termasuk antrean dari hari sebelumnya. Riwayat difilter menurut tanggal jadwal.</p>
</aside></div>
