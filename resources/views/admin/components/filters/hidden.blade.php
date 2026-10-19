@foreach($values ?? [] as $hiddenKey => $hiddenValue)
    @php($hiddenName = isset($prefix) ? $prefix.'['.$hiddenKey.']' : $hiddenKey)
    @if(is_array($hiddenValue))
        @include('admin.components.filters.hidden', ['values' => $hiddenValue, 'prefix' => $hiddenName])
    @elseif(is_scalar($hiddenValue))
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
    @endif
@endforeach
