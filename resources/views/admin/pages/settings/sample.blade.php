<article class="design-system__sample" data-ds-sample data-ds-properties="{{ $properties ?? 'font-size,line-height,height,min-height,padding,border,border-radius,box-shadow' }}">
    <div class="design-system__sample-label">{{ $label }}</div>
    <code class="design-system__usage">{{ $usage }}</code>
    <div class="design-system__preview">{{ $slot }}</div>
    <dl class="design-system__specs" data-ds-specs></dl>
</article>
