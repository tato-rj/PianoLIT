@component('mail::welcome.message', ['websiteUrl' => $websiteUrl, 'unsubscribeUrl' => $unsubscribeUrl])
@component('mail::welcome.hero', ['firstName' => $firstName, 'websiteUrl' => $websiteUrl])
Browse piano pieces by level, mood, style, and technique.<br>
Use available scores, videos, and recordings for practice.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our iOS app', 'image' => $websiteUrl.mix('images/emails/welcome/tablet-iphone.png'), 'alt' => 'PianoLIT on a tablet and in the iPhone app', 'links' => [['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
Use PianoLIT on iPhone and iPad. Sign in with the same account you use on the web.
@endcomponent

@include('emails.welcome.shared')
@endcomponent
