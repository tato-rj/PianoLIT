@php
    $ratingId = $id ?? 'rating';
    $ratingName = $name ?? 'rating';
    $ratingErrors = $errors->getBag($bag ?? 'default');
    $ratingValue = old($ratingName, $value ?? 0);
@endphp
<fieldset class="rating-field mb-3" @if($ratingErrors->has($ratingName)) aria-describedby="{{ $ratingId }}-error" @endif>
    <legend class="small mb-2">{{ $label ?? 'Your rating' }} <span class="text-muted">(required)</span></legend>
    <div class="rating-field__choices d-inline-flex">
        @for($star = 1; $star <= 5; $star++)
        <input class="visually-hidden" type="radio" id="{{ $ratingId }}-{{ $star }}" name="{{ $ratingName }}" value="{{ $star }}" @if((string) $ratingValue === (string) $star) checked @endif required @if($ratingErrors->has($ratingName)) aria-invalid="true" @endif>
        <label class="rating-field__star m-0" for="{{ $ratingId }}-{{ $star }}">
            @icon('star', ['mr' => 0])<span class="visually-hidden">{{ $star }} {{ str_plural('star', $star) }}</span>
        </label>
        @endfor
    </div>
    @if($ratingErrors->has($ratingName))<p class="text-danger small mb-0" id="{{ $ratingId }}-error">{{ $ratingErrors->first($ratingName) }}</p>@endif
</fieldset>
