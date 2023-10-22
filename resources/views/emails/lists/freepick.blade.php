@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 class="mail-title"><span class="text-blue">Free</span> pick of the week</h1>

@component('mail::highlights.cover', ['piece' => $piece])	
@endcomponent

<div class="mb-6">
<p style="margin-bottom: -10px"><strong>💡 What's it about?</strong></p>
<p style="white-space: pre-wrap;">
{{$piece->description}}
</p>
</div>

@component('mail::panel')
# 🎉Share your own recordings!
<p class="mb-2">You can now upload your performances, share your talent and get feedback from PianoLIT users from all over the world.</p>
<p class="mb-3">*Available on <strong>Webapp only</strong></p>
<img src="https://storage.googleapis.com/pianolit-app/misc/stage%20demo.gif">
@endcomponent

@component('mail::button', ['url' => 'https://my.pianolit.com/'])
Open with the Webapp
@endcomponent

@component('mail::button', ['url' => config('app.stores.ios'), 'color' => 'outline-primary'])
Open with the iOS app
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::panel')

<p><strong>Don't miss out on more from PianoLIT:</strong></p>

<p>🎹 On the <u>Webapp</u> you can now upload <strong>your own video recordings</strong>. Just navigate to the new <i>Stage</i> tab and follow the instructions to get started.</p>
{{-- <p>😎 We are on the <strong>Metaverse</strong>! PianoLIT is partnering with Spatial to promote weekly concerts and events on all topics related to music. Simply jump in and enjoy the show. <a href="https://pianolit.com/metaverse" target="_blank">Check out our schedule here</a></p> --}}

<p>▶️ On our <strong>Youtube Channel</strong> we post not only recordings from the pieces we like the most, but also special in-depth videos about harmonic analysis. <a href="https://youtube.com/pianolit" target="_blank">Subscribe here</a></p>

<p>📚 Check out our <strong>eBooks</strong>! Dive into your favorite music topics and keep on learning (and if you are a member you can download them all for FREE). <a href="https://pianolit.com/ebooks" target="_blank">Find our store here</a></p>

@endcomponent

@include('mail::signature')
@endcomponent
