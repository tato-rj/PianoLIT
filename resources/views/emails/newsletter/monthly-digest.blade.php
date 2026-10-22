@component('mail::message')
# What’s new at PianoLIT

{{ $monthLabel }}

Here’s what’s new across PianoLIT. Find something to learn, explore, or enjoy.

@forelse($items as $item)
## {{ $item->title }}

{{ $item->hub }} · {{ $item->type }}

{{ $item->description }}

@component('mail::button', ['url' => $item->url])
Explore {{ $item->hub }}
@endcomponent
@empty
New releases will appear here in the next newsletter. Months without new content do not generate an email.
@endforelse

Happy exploring,<br>
The PianoLIT team

@if($unsubscribeUrl)
You’re receiving this monthly email because you subscribed to PianoLIT news. [Unsubscribe from the newsletter]({{ $unsubscribeUrl }}).
@endif
@endcomponent
