@component('mail::editorial.message', ['title' => 'Welcome to PianoLIT Premium', 'preheader' => 'Your Premium membership is active. Let’s play something you love.', 'websiteUrl' => $websiteUrl, 'footerReason' => 'Your PianoLIT Premium membership', 'unsubscribeUrl' => $websiteUrl.route('users.profile', [], false).'?tab=email-preferences'])
@component('mail::welcome.hero', ['title' => 'You’re all set!', 'firstName' => $firstName, 'websiteUrl' => $websiteUrl])
Welcome to Premium, {{ $firstName }}.<br>
Your next favorite piece is waiting.
@endcomponent

@component('mail::welcome.section', ['image' => $websiteUrl.mix('images/emails/welcome/app-devices.png'), 'alt' => 'PianoLIT on a computer and in the iOS app', 'links' => [['url' => route('webapp.discover'), 'label' => 'Start exploring', 'icon' => $websiteUrl.mix('images/emails/welcome/pianolit-white.png')]]])
@endcomponent

@component('mail::welcome.section', ['title' => $plan, 'links' => [['url' => route('webapp.membership.edit'), 'label' => 'My Membership', 'kind' => 'secondary']]])
{{ $trial ? 'Your free trial is active.' : 'Your membership is active.' }}

@if($renewalDate)
{{ $trial ? 'First billing date' : 'Next renewal' }}: **{{ $renewalDate }}**
@endif
@if($trial)
@slot('note')
No payment was due today.
@endslot
@endif
@endcomponent
@endcomponent
