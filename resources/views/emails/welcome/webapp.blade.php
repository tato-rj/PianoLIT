@component('mail::welcome.message', ['websiteUrl' => $websiteUrl, 'unsubscribeUrl' => $unsubscribeUrl])
@component('mail::welcome.hero', ['firstName' => $firstName, 'websiteUrl' => $websiteUrl])
PianoLIT is where pianists discover new pieces and find inspiration<br>
to play only what they love.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our iOS app', 'image' => $websiteUrl.mix('images/emails/welcome/tablet-iphone.jpg'), 'alt' => 'PianoLIT on a tablet and in the iPhone app', 'links' => [['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
Take PianoLIT with you. The iOS app puts new discoveries within reach, wherever you are. Just sign in with the same account you use on the web.
@endcomponent

@include('emails.welcome.shared')
@endcomponent
