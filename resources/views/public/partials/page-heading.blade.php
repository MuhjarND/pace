<div class="citizen-intro citizen-intro-compact citizen-task-heading {{ !empty($illustration) ? 'citizen-task-illustrated' : '' }}">
    <div><h1>{{ $heading }}</h1><p>{{ $description }}</p></div>
    @if(!empty($illustration))
    @include('public.partials.illustration',['scene'=>$illustration,'priority'=>false])
    @else<i class="{{ $icon }} citizen-task-icon" aria-hidden="true"></i>@endif
</div>
