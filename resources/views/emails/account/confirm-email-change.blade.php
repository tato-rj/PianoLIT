@component('mail::message')
# Confirm your new email

You requested to use this email address for your PianoLIT account. Sign in to the account that requested the change, then confirm below.

@component('mail::button', ['url' => $confirmationUrl])
Confirm email address
@endcomponent

This link expires in one hour. Your current email will keep working until you confirm the change. If you didn’t request this, you can ignore this email.

The PianoLIT team
@endcomponent
