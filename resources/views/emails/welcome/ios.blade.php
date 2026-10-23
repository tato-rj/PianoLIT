@component('mail::welcome.message')
@component('mail::welcome.hero', ['firstName' => $firstName, 'image' => $websiteUrl.mix('images/emails/welcome/floating-logo.png')])
PianoLIT is where pianists discover new pieces and find inspiration<br>
to play only what they love.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our Webapp.', 'image' => $websiteUrl.mix('images/emails/welcome/webapp-devices.jpg'), 'alt' => 'PianoLIT web app on a computer and iPhone', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app']]])
Give your discoveries a little more room. Open the PianoLIT web app on your computer or tablet, and sign in with the same account you use in the iOS app.
@endcomponent

@include('emails.welcome.hubs')
@endcomponent
