@foreach($action as $data)
    @if($data['route'])
    @include('admin.components.table-action', ['tableAction' => ['url' => $data['route'], 'label' => $data['title'], 'icon' => $data['icon'], 'iconOnly' => true, 'attributes' => ['target' => !array_key_exists('target', $data) ? '_blank' : null, 'rel' => !array_key_exists('target', $data) ? 'noopener noreferrer' : null]]])
    @endif
@endforeach
