@php($statusTones = ['available' => 'green', 'limited' => 'warning', 'blocked' => 'red'])
<select name="{{ $name }}" class="admin-table__badge admin-table__badge--{{ $tone ?? $statusTones[$value] }} status-select" aria-label="{{ $label }}" data-status-select data-status-notes="{{ json_encode($notes ?? []) }}" @if($disabled ?? false) disabled @endif>
    @foreach($options as $option)
        <option value="{{ $option }}" @if($option === $value) selected @endif>{{ $option === $value ? ($selectedLabel ?? ucfirst($option)) : ucfirst($option) }}</option>
    @endforeach
</select>
@if($disabled ?? false)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
