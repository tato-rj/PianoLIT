<nav class="d-flex flex-wrap gap-2 py-4 mb-4 border-top border-bottom {{ $class ?? '' }}" aria-label="{{ $label ?? 'Topics' }}" @if($preview ?? false) data-ds-preview @endif>
    @foreach($items as $item)
        <a href="{{ $item['url'] }}" class="btn btn-sm btn-{{ $item['variant'] ?? (!empty($item['active']) ? 'primary' : 'secondary') }}" @if(!empty($item['active'])) aria-current="page" @endif>{{ $item['label'] }}</a>
    @endforeach
</nav>
