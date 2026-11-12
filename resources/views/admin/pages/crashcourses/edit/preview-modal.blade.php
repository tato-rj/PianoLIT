@component('components.dialogs.modal', ['id' => $previewId, 'header' => $previewTitle, 'centered' => true])
    @slot('body')
        @isset($subject)
            <p class="small text-muted">{{ $subject }}</p>
        @endisset
        <form method="GET" action="{{ $sendUrl }}" class="mb-3" disable-on-submit>
            @input(['id' => $previewId.'-email', 'label' => 'Email address', 'name' => 'email', 'type' => 'email', 'limit' => 254, 'bag' => 'default'])
            <button type="submit" class="btn btn-primary btn-sm">Send preview</button>
        </form>
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">@icon('external-link')Open in browser</a>
    @endslot
@endcomponent
