@component('mail::welcome.message', ['websiteUrl' => $websiteUrl, 'unsubscribeUrl' => $unsubscribeUrl])
@component('mail::welcome.hero', ['firstName' => $firstName, 'websiteUrl' => $websiteUrl])
PianoLIT is where pianists discover new pieces and find inspiration<br>
to play only what they love.
@endcomponent

@component('mail::welcome.section', ['title' => 'Access PianoLIT from anywhere', 'image' => $websiteUrl.mix('images/emails/welcome/app-devices.jpg'), 'alt' => 'PianoLIT on a computer and in the iOS app', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app'], ['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
At your piano or on the go, discover your next favorite piece in the web app or the iOS app. Sign in to either with the same PianoLIT account.
@endcomponent

@include('emails.welcome.shared')
@endcomponent
