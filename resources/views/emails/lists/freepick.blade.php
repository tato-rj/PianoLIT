@component('mail::editorial.message', ['title' => 'Free pick of the week', 'preheader' => $piece->medium_name.' by '.$piece->composer->name.'. Your next piano discovery is here.', 'websiteUrl' => $websiteUrl, 'footerReason' => 'You’re receiving this email because you are subscribed to PianoLIT Free Picks.', 'unsubscribeUrl' => $unsubscribeUrl, 'unsubscribeLabel' => $subscription ? 'Unsubscribe' : 'Unsubscribe / email preferences', 'emailClass' => 'freepick-email'])
@component('mail::freepick.header', ['title' => 'Free pick of the week', 'websiteUrl' => $websiteUrl])
@endcomponent

@component('mail::freepick.hero', ['piece' => $piece, 'url' => $pieceUrl, 'websiteUrl' => $websiteUrl])
@component('mail::freepick.piece', ['piece' => $piece, 'url' => $pieceUrl, 'websiteUrl' => $websiteUrl])
@endcomponent

@component('mail::freepick.introduction', ['url' => $pieceUrl, 'button' => 'Explore this piece'])
{{ $piece->description }}
@endcomponent

@if($hasScore || $hasPerformance || $hasSynthesia || $hasAudio || $hasVideoComments)
@component('mail::freepick.features', ['title' => "Let's dive in", 'introduction' => 'Sign in with your PianoLIT account to explore this week’s piece while it’s featured.'])
@if($hasScore)
@component('mail::editorial.feature', ['title' => 'Score & annotations', 'image' => $websiteUrl.mix('images/emails/welcome/premium-file-music.png')])
Read the full score and keep your own practice markings.
@endcomponent
@endif

@if($hasPerformance)
@component('mail::editorial.feature', ['title' => 'Performance video', 'image' => $websiteUrl.mix('images/emails/welcome/premium-video.png')])
Watch the full performance for ideas on sound and phrasing.
@endcomponent
@endif

@if($hasVideoComments)
@component('mail::editorial.feature', ['title' => 'Comments as you watch', 'image' => $websiteUrl.mix('images/emails/welcome/premium-video.png')])
Read helpful comments in sync with the video, and jump straight to the passages they explain.
@endcomponent
@endif

@if($hasSynthesia)
@component('mail::editorial.feature', ['title' => 'Synthesia', 'image' => $websiteUrl.mix('images/emails/welcome/premium-piano.png')])
See every note and how both hands fit together.
@endcomponent
@endif

@if($hasAudio)
@component('mail::editorial.feature', ['title' => 'Audio for practice', 'image' => $websiteUrl.mix('images/emails/welcome/premium-headphones.png')])
@if($hasSeparateHands)
Listen to the full recording and available separate-hand audio.
@else
Get to know the piece with the full recording.
@endif
@endcomponent
@endif

@endcomponent
@endif

@endcomponent

@if(count($latestPieces))
@component('mail::welcome.pieces', ['title' => 'More music to discover', 'pieces' => $latestPieces, 'url' => $libraryUrl, 'button' => 'Browse the PianoLIT library', 'variant' => 'freepick-latest'])
Some of our latest additions. Explore the full library with Premium.
@endcomponent
@endif

@component('mail::freepick.premium', ['title' => 'Keep the inspiration going', 'image' => $websiteUrl.mix('images/emails/welcome/app-devices.jpg'), 'url' => $premiumUrl, 'button' => 'Go Premium'])
This week is just the beginning. With PianoLIT Premium, keep exploring the full library of pieces, available videos and recordings, and public-domain scores.

Save your markings, build a repertoire you love, and download or print public-domain scores for your piano. The same account works on the web and in the iOS app.
@endcomponent
@endcomponent
