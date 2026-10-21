@component('mail::welcome.message')
@component('mail::welcome.hero', ['firstName' => $firstName, 'image' => $websiteUrl.mix('images/emails/welcome/floating-logo.png')])
A world of piano music, ready to explore.<br>
Find pieces you love. Listen, learn, and make them your own.
@endcomponent

@component('mail::welcome.section', ['title' => 'Try our Webapp.', 'image' => $websiteUrl.mix('images/emails/welcome/webapp-devices.jpg'), 'alt' => 'PianoLIT web app on a computer and iPhone', 'links' => [['url' => $webappUrl, 'label' => 'Open the web app']]])
Give your discoveries a little more room. Open the PianoLIT web app on your computer or tablet, and sign in with the same account you use in the iOS app.
@endcomponent

@component('mail::welcome.section', ['title' => 'A world to explore.', 'image' => $websiteUrl.mix('images/emails/welcome/resource-browser.jpg'), 'alt' => 'PianoLIT’s interactive Circle of Fifths theory tool', 'links' => [['url' => $websiteUrl, 'label' => 'Explore PianoLIT.com']]])
PianoLIT.com is your home for musical curiosity. Explore theory tools, play a quiz, or find a fresh idea on the blog.
@endcomponent
@endcomponent
