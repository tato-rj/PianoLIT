@include('admin.components.filters.status-select', ['name' => $cell['name'], 'value' => $cell['state'], 'label' => $cell['accessibleLabel'], 'selectedLabel' => $cell['label'], 'tone' => $cell['tone'], 'notes' => $cell['notes'], 'options' => $cell['options'], 'disabled' => $cell['locked']])
<small data-access-note>{{ $cell['note'] }}</small>
