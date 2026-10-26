<div class="segmented-control" role="radiogroup" aria-label="{{ $label }}" @if($preview ?? false) data-ds-preview @endif>
    @foreach($options as $optionValue => $optionLabel)
        <input class="visually-hidden segmented-control__input" type="radio" name="{{ $name }}" id="{{ $id }}-{{ $optionValue }}" value="{{ $optionValue }}"
            @if(isset($dataAttribute)) data-{{ $dataAttribute }}="{{ $optionValue }}" @endif
            @if((string) ($value ?? array_key_first($options)) === (string) $optionValue) checked @endif>
        <label class="segmented-control__option" for="{{ $id }}-{{ $optionValue }}">{{ $optionLabel }}</label>
    @endforeach
</div>
