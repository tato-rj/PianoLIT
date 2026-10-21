@component('mail::welcome.message')
@component('mail::welcome.hero', ['firstName' => $firstName, 'image' => $websiteUrl.mix('images/emails/welcome/floating-logo.png')])
Here is where pianists discover new pieces and find inspiration<br>
to play only what they love.
@endcomponent

@component('mail::welcome.section', ['title' => 'Access PianoLIT from anywhere.', 'image' => $websiteUrl.mix('images/emails/welcome/app-devices.jpg'), 'alt' => 'PianoLIT on a computer and in the iOS app', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app'], ['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
At your piano or on the go, discover your next favorite piece in the web app or the iOS app. Sign in to either with the same PianoLIT account.
@endcomponent

@component('mail::welcome.section', ['title' => 'A world to explore.', 'image' => $websiteUrl.mix('images/emails/welcome/resource-browser.jpg'), 'alt' => 'PianoLIT’s interactive Circle of Fifths theory tool', 'links' => [['url' => $websiteUrl, 'label' => 'Explore PianoLIT.com']]])
PianoLIT.com is your home for musical curiosity. Explore theory tools, play a quiz, or find a fresh idea on the blog.
@endcomponent
@endcomponent
