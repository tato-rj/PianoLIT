@component('mail::welcome.benefits', ['title' => 'Go further with PianoLIT Premium', 'url' => $premiumUrl, 'button' => 'Go Premium'])
@component('mail::welcome.benefit', ['title' => 'Enjoy every performance', 'image' => $websiteUrl.mix('images/emails/welcome/premium-video.png')])
Watch complete performances and discover the music beyond the preview.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'See every note', 'image' => $websiteUrl.mix('images/emails/welcome/premium-piano.png')])
Follow along with full Synthesia videos, one phrase at a time.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'Listen a little closer', 'image' => $websiteUrl.mix('images/emails/welcome/premium-headphones.png')])
Enjoy full recordings, with separate-hand audio whenever it’s available.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'Make the score your own', 'image' => $websiteUrl.mix('images/emails/welcome/premium-file-music.png')])
Read and annotate public-domain scores, then download or print the originals for your piano.
@endcomponent
@endcomponent
