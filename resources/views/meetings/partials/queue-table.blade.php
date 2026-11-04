<div class="consultation-table-wrap">
    <table class="consultation-table">
        <thead><tr><th>Antrean</th><th>Jadwal (WIT)</th><th>Status</th></tr></thead>
        <tbody id="queueRows">
        @forelse($rows as $row)
        <tr class="{{ !empty($row['is_you']) ? 'is-you' : '' }}"><td><strong>{{ $row['number'] }}</strong>{{ !empty($row['is_you']) ? ' · Anda' : '' }}</td><td>{{ $row['date'] }} · {{ $row['time'] }}</td><td>{{ $row['status'] }}</td></tr>
        @empty<tr><td colspan="3" class="consultation-copy">Belum ada antrean aktif. Anda dapat bergabung langsung jika memilih Sekarang.</td></tr>@endforelse
        </tbody>
    </table>
</div>
