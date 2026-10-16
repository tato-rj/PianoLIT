@component('mail::message')
# Welcome to PianoLIT!

Hi {{ $firstName }}, we're happy you're here.

Your PianoLIT account is ready. Explore the repertoire, find music you love, and save your favorite pieces as you go.

@component('mail::button', ['url' => route('webapp.discover')])
Explore PianoLIT
@endcomponent

If you have any questions, reply to this email or contact [contact@pianolit.com](mailto:contact@pianolit.com). We're here to help.

Enjoy the music,<br>
The PianoLIT team
@endcomponent
