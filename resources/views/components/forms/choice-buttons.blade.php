<div class="row g-2 row-gap-2" role="group" aria-label="{{ $label }}" data-choice-buttons="{{ $name }}" @if($preview ?? false) data-ds-preview @endif>
    @foreach($options as $key => $option)
        <div class="col-6">
            <button type="button" class="btn {{ $key === $value ? 'btn-primary' : 'btn-secondary' }} w-100 d-flex flex-column align-items-center gap-2 py-3" data-choice="{{ $key }}" aria-pressed="{{ $key === $value ? 'true' : 'false' }}">
                @icon($option['icon'], ['mr' => 0])
                <span>{{ $option['label'] }}</span>
            </button>
        </div>
    @endforeach
</div>
