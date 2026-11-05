@component('mail::welcome.message', ['websiteUrl' => $websiteUrl, 'unsubscribeUrl' => $unsubscribeUrl])
@component('mail::welcome.hero', ['firstName' => $firstName, 'websiteUrl' => $websiteUrl])
Browse piano pieces by level, mood, style, and technique.<br>
Use available scores, videos, and recordings for practice.
@endcomponent

@component('mail::welcome.section', ['title' => 'Access PianoLIT from anywhere', 'image' => $websiteUrl.mix('images/emails/welcome/app-devices.png'), 'alt' => 'PianoLIT on a computer and in the iOS app', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app', 'icon' => $websiteUrl.mix('images/emails/welcome/pianolit-white.png')], ['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
Use the web app or iOS app to browse pieces and practice resources. Sign in to either with the same PianoLIT account.
@endcomponent

@include('emails.welcome.shared')
@endcomponent
