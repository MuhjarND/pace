@php($artMetadata = config('ui.illustrations.'.$scene))
@if($artMetadata)
<img class="ui-illustration {{ $illustrationClass ?? '' }}" src="{{ asset('images/'.$artMetadata['file']) }}" width="{{ $artMetadata['width'] }}" height="{{ $artMetadata['height'] }}" alt="" aria-hidden="true" loading="{{ !empty($priority) ? 'eager' : 'lazy' }}" fetchpriority="{{ !empty($priority) ? 'high' : 'auto' }}" decoding="async">
@endif
