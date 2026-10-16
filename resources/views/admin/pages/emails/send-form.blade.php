<form id="email-preview-send" class="admin-emails__send" method="POST" action="{{ route('admin.emails.send') }}">
    @csrf
    @input(['bag' => 'default', 'id' => 'preview-recipient', 'type' => 'email', 'name' => 'email', 'label' => 'Send previews to', 'value' => $recipient, 'limit' => 254, 'autocomplete' => 'email', 'describedBy' => 'preview-send-help'])
    <p id="preview-send-help">Sample content · Subjects begin with [Preview] · Account links are inactive.</p>
</form>
