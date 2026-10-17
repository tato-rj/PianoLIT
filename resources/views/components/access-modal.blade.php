@component('components.modal', [
    'id' => $id, 'titleId' => $id.'-heading', 'descriptionId' => $id.'-description', 'centered' => true,
    'data' => ['access-prompt' => $kind, 'access-copy' => json_encode($actionCopies ?? [])],
    'options' => ['header' => ['raw' => true]],
])
    @slot('header')@include('components.access-prompt-icon')@endslot
    @slot('body')
        @include('components.access-prompt-body', ['headingId' => $id.'-heading', 'descriptionId' => $id.'-description'])
    @endslot
@endcomponent
