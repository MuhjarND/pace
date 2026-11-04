<div class="page-heading {{ !empty($illustration) ? 'has-illustration' : '' }}">
    <div>
        <p class="eyebrow">{{ $eyebrow ?? 'Pelayanan terpadu satu pintu' }}</p>
        <h1>{{ $heading }}</h1>
        @isset($description)<p class="page-description">{{ $description }}</p>@endisset
    </div>
    @if(isset($aside) || !empty($illustration))<div class="page-heading-aside">@if(!empty($illustration))@include('public.partials.illustration',['scene'=>$illustration,'priority'=>false])@endif @isset($aside)<span>{{ $aside }}</span>@endisset</div>@endif
</div>
