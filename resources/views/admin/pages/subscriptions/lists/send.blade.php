@php
    $recentlySent = $list->last_sent_at && $list->last_sent_at->gt(now()->subDay());
    $sendDisabled = !$list->subscribers_count || $recentlySent;
@endphp
@component('components.dialogs.modal', ['id' => 'list-send-'.$list->id, 'header' => 'Send '.e($list->name).' email', 'centered' => true, 'options' => ['footer' => ['raw' => true]], 'show' => (($errors->has('email') || old('email') !== null) && is_scalar(old('preview_list_id')) && (string) old('preview_list_id') === (string) $list->id) ? true : null])
    @slot('body')
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <h6 class="mb-0">Preview</h6>
            <a class="small d-inline-flex align-items-center gap-2 text-nowrap" href="{{ route('admin.subscriptions.lists.preview', $list) }}" target="_blank" rel="noopener noreferrer">@icon('external-link', ['mr' => 0])Preview in browser</a>
        </div>
        <form method="POST" action="{{ route('admin.subscriptions.lists.send-to', $list) }}" disable-on-submit>
            @csrf
            @input(['bag' => 'default', 'id' => 'list-recipient-'.$list->id, 'type' => 'email', 'name' => 'email', 'label' => 'Preview recipient', 'value' => $recipient, 'limit' => 254, 'autocomplete' => 'email', 'grid' => 'mb-2'])
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-outline-secondary">@icon('mail')Send preview email</button>
            </div>
        </form>

        <div class="bg-light rounded p-3 mt-4">
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">@icon('users', ['mr' => 0, 'classes' => 'icon-size-lg'])</span>
                <div>
                    <strong class="d-block">{{ number_format($list->subscribers_count) }} {{ str_plural('subscriber', $list->subscribers_count) }}</strong>
                    <span class="small text-muted">{{ $list->name === \App\EmailList::FREE_PICK ? 'Will receive the current free pick.' : 'Will receive this list’s email.' }}</span>
                </div>
            </div>
            @if($recentlySent)
                <p class="small text-muted mt-3 mb-0">Next send available {{ $list->last_sent_at->copy()->addDay()->format('M j \a\t g:i a') }}. Previews are available now.</p>
            @elseif(!$list->subscribers_count)
                <p class="small text-muted mt-3 mb-0">Add subscribers before sending this list.</p>
            @endif
        </div>
    @endslot
    @slot('footer')
        <div class="d-flex justify-content-end gap-2 px-4 pb-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form method="POST" action="{{ route('admin.subscriptions.lists.send', $list) }}" disable-on-submit>
                @csrf
                <button type="submit" class="btn btn-primary text-nowrap" @if($sendDisabled) disabled @endif aria-label="Send to all {{ number_format($list->subscribers_count) }} subscribers">@icon('mail')Send to all</button>
            </form>
        </div>
    @endslot
@endcomponent
