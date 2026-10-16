<form id="email-preview-send" class="admin-emails__send" method="POST" action="{{ route('admin.emails.send') }}">
    @csrf
    <label for="preview-recipient">Send previews to
        <input id="preview-recipient" type="email" name="email" value="{{ is_string(old('email')) ? old('email') : $recipient }}" required maxlength="254" autocomplete="email" aria-describedby="preview-send-help" @if($errors->has('email')) aria-invalid="true" @endif>
    </label>
    <p id="preview-send-help">Sample content · Subjects begin with [Preview] · Account links are inactive.</p>
    @if($errors->has('email'))
    <p class="text-danger" role="alert">{{ $errors->first('email') }}</p>
    @endif
</form>
