<ol class="citizen-guide-steps">
    @foreach($steps as $step)
    <li><span class="citizen-guide-number" aria-hidden="true">{{ $loop->iteration }}</span><strong>{{ $step[0] }}</strong><p>{{ $step[1] }}</p></li>
    @endforeach
</ol>
