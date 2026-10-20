<article class="design-system__sample" @if($editable ?? true) data-ds-sample data-ds-properties="{{ $properties ?? 'font-size,line-height,height,min-height,padding,border,border-radius,box-shadow' }}" @endif>
    <div class="design-system__sample-label">{{ $label }}</div>
    @isset($usage)<code class="design-system__usage">{{ $usage }}</code>@endisset
    <div class="design-system__preview">{{ $slot }}</div>
    @if($editable ?? true)<dl class="design-system__specs" data-ds-specs></dl>@endif
</article>
