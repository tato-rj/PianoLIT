<div class="pill-filters nav {{ ($scrollable ?? false) ? 'pill-filters--scrollable custom-scroll' : '' }} {{ $class ?? '' }}" role="tablist" aria-label="{{ $label }}" @if($preview ?? false) data-ds-preview @endif>
    @foreach($items as $item)
    @php($selected = $item['key'] === $active)
    <button class="nav-link {{ $selected ? 'active' : '' }}" id="{{ $id }}-{{ $item['key'] }}-tab" data-bs-toggle="pill" data-bs-target="#{{ $id }}-{{ $item['key'] }}" type="button" role="tab" aria-controls="{{ $id }}-{{ $item['key'] }}" aria-selected="{{ $selected ? 'true' : 'false' }}" tabindex="{{ $selected ? '0' : '-1' }}" @if($item['disabled'] ?? false) disabled aria-disabled="true" @endif>{{ $item['label'] }}</button>
    @endforeach
</div>
