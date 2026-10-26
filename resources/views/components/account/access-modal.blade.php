@component('components.dialogs.modal', [
    'id' => $id, 'titleId' => $id.'-heading', 'descriptionId' => $id.'-description', 'centered' => true,
    'data' => ['access-prompt' => $kind, 'access-copy' => json_encode($actionCopies ?? [])],
    'options' => ['header' => ['raw' => true]],
])
    @slot('header')@include('components.account.access-prompt-icon')@endslot
    @slot('body')
        @include('components.account.access-prompt-body', ['headingId' => $id.'-heading', 'descriptionId' => $id.'-description'])
    @endslot
@endcomponent
