@if ($errors->has($field))
<div class="invalid-feedback d-block ml-2">
  {{ $errors->first($field) }}
</div>
@endif