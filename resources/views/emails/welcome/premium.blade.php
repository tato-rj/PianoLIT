@component('mail::welcome.benefits', ['title' => 'PianoLIT Premium features', 'url' => $premiumUrl, 'button' => 'Go Premium'])
@component('mail::welcome.benefit', ['title' => 'Full performance videos', 'image' => $websiteUrl.mix('images/emails/welcome/premium-video.png')])
Watch complete performance videos.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'Full Synthesia videos', 'image' => $websiteUrl.mix('images/emails/welcome/premium-piano.png')])
Follow along with full Synthesia videos, one phrase at a time.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'Full recordings', 'image' => $websiteUrl.mix('images/emails/welcome/premium-headphones.png')])
Enjoy full recordings, with separate-hand audio whenever it’s available.
@endcomponent

@component('mail::welcome.benefit', ['title' => 'Score annotations and downloads', 'image' => $websiteUrl.mix('images/emails/welcome/premium-file-music.png')])
Read and annotate public-domain scores, then download or print the originals for your piano.
@endcomponent
@endcomponent
