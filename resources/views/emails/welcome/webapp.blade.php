@component('mail::welcome.message')
@component('mail::welcome.hero', ['firstName' => $firstName, 'image' => $websiteUrl.mix('images/emails/welcome/floating-logo.png')])
PianoLIT is where pianists discover new pieces and find inspiration<br>
to play only what they love.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our iOS app.', 'image' => $websiteUrl.mix('images/emails/welcome/tablet-iphone.jpg'), 'alt' => 'PianoLIT on a tablet and in the iPhone app', 'links' => [['url' => $iosUrl, 'label' => 'Get the iOS app', 'kind' => 'app-store', 'image' => $websiteUrl.mix('images/emails/welcome/app-store.png')]]])
Take PianoLIT with you. The iOS app puts new discoveries within reach, wherever you are. Just sign in with the same account you use on the web.
@endcomponent

@component('mail::welcome.section', ['title' => 'A world to explore.', 'image' => $websiteUrl.mix('images/emails/welcome/resource-browser.jpg'), 'alt' => 'PianoLIT’s interactive Circle of Fifths theory tool', 'links' => [['url' => $websiteUrl, 'label' => 'Explore PianoLIT.com']]])
PianoLIT.com is your home for musical curiosity. Explore theory tools, play a quiz, or find a fresh idea on the blog.
@endcomponent
@endcomponent
