<div class="pt-2 pb-2">
    <a href="{{$badge['link']}}" target="_blank" class="badge-lg badge-{{$badge['color']}} mb-3" style="text-decoration: none;">{{strtoupper($badge['name'])}}</a>
    <h1 class="text-lg">{{$title}}</h1>
    
    {{Illuminate\Mail\Markdown::parse($slot)}}

    @include('mail::divider', ['orientation' => 'vertical'])
</div>