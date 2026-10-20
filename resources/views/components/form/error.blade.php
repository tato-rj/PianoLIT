@php($fieldErrors = $errors->getBag($bag ?? 'default'))
@if ($fieldErrors->has($field))
    <span class="invalid-feedback" role="alert">
        <strong>{{ $fieldErrors->first($field) }}</strong>
    </span>
@endif
