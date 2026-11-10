@php
    $tinyBag = $bag ?? 'default';
    $tinyId = $id ?? 'editor';
    $tinyErrors = $errors->getBag($tinyBag);
    $tinyOldValue = old($name);
    $tinyValue = \Illuminate\Support\Arr::has(session()->getOldInput(), $name) ? (is_scalar($tinyOldValue) ? $tinyOldValue : '') : ($value ?? null);
@endphp
<div class="form-group">
    @include('components.forms.label', ['id' => $tinyId])
    <textarea class="form-control rounded {{ validate($tinyErrors, $name) }}" id="{{ $tinyId }}" name="{{ $name }}" rows="{{ $rows ?? 26 }}" @if($tinyErrors->has($name)) aria-invalid="true" @endif>{{ $tinyValue }}</textarea>
    @include('components.forms.error', ['bag' => $tinyBag, 'field' => $name, 'errorClasses' => 'd-block'])
</div>
