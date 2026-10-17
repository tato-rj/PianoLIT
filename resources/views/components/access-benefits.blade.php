@if(count($benefits))
<ul class="access-benefits list-unstyled" aria-label="{{ ($kind ?? 'signup') === 'premium' ? 'Premium benefits' : 'Free account benefits' }}">
    @foreach($benefits as $benefit)
        <li><span class="access-benefits__check text-green" aria-hidden="true">@icon('check', ['mr' => 0])</span><span>{{ $benefit }}</span></li>
    @endforeach
</ul>
@endif
