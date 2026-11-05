@component('mail::welcome.message', ['websiteUrl' => $websiteUrl, 'unsubscribeUrl' => $unsubscribeUrl])
@component('mail::welcome.hero', ['firstName' => $firstName, 'websiteUrl' => $websiteUrl])
Browse piano pieces by level, mood, style, and technique.<br>
Use available scores, videos, and recordings for practice.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our Webapp', 'image' => $websiteUrl.mix('images/emails/welcome/webapp-devices.png'), 'alt' => 'PianoLIT web app on a computer and iPhone', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app', 'icon' => $websiteUrl.mix('images/emails/welcome/pianolit-white.png')]]])
Open the PianoLIT web app on your computer or tablet. Sign in with the same account you use in the iOS app.
@endcomponent

@include('emails.welcome.shared')
@endcomponent
