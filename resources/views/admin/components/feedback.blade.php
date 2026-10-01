@if ($errors->has($field))
<div class="invalid-feedback d-block ms-2">
  {{ $errors->first($field) }}
</div>
@endif