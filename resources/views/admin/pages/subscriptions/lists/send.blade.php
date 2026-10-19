@component('components.modal', ['id' => 'list-send-'.$list->id, 'header' => 'Send '.$list->name.' email', 'centered' => true, 'show' => ($errors->has('email') || old('email') !== null) ? true : null])
    @slot('body')
        <form method="POST" action="{{ route('admin.subscriptions.lists.send-to', $list) }}" disable-on-submit>
            @csrf
            @input(['bag' => 'default', 'id' => 'list-recipient-'.$list->id, 'type' => 'email', 'name' => 'email', 'label' => 'Send a preview to', 'value' => $recipient, 'limit' => 254, 'autocomplete' => 'email'])
            <button type="submit" class="btn btn-outline-secondary">@icon('mail')Send preview</button>
        </form>
        <hr class="my-4">
        <p>The current free pick will be sent to <strong>{{ number_format($list->subscribers_count) }} {{ str_plural('subscriber', $list->subscribers_count) }}</strong>.</p>
        @if($list->last_sent_at && $list->last_sent_at->gt(now()->subDay()))
            <p class="text-muted mb-0">This list was sent within the last 24 hours. You can still send a preview.</p>
        @elseif(!$list->subscribers_count)
            <p class="text-muted mb-0">Add subscribers before sending this list.</p>
        @endif
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="{{ route('admin.subscriptions.lists.send', $list) }}" disable-on-submit>
            @csrf
            <button type="submit" class="btn btn-primary" @if(!$list->subscribers_count || ($list->last_sent_at && $list->last_sent_at->gt(now()->subDay()))) disabled @endif>@icon('mail')Send to all subscribers</button>
        </form>
    @endslot
@endcomponent
