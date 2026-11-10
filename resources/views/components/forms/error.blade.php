@php($fieldErrors = $errors->getBag($bag ?? 'default'))
@if ($fieldErrors->has($field))
    <span class="invalid-feedback{{ !empty($errorClasses) ? ' '.$errorClasses : '' }}" role="alert">
        <strong>{{ $fieldErrors->first($field) }}</strong>
    </span>
@endif
