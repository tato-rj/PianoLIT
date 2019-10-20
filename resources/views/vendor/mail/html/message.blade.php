@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            <img src="{{ asset('images/brand/app-icon.png') }}" width="80" style="border-radius: 22px">
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            <div style="margin: 8px 0">
                <div style="margin-bottom: 32px">
                    <a target="_blank" href="{{config('services.channels.facebook')}}" class="link-none">
                        <img style="display: inline-block; margin: 0 12px; opacity: .4" width="22" src="{{asset('images/emails/facebook.png')}}">
                    </a>
                    <a target="_blank" href="{{config('services.channels.youtube')}}" class="link-none">
                        <img style="display: inline-block; margin: 0 12px; opacity: .4" width="22" src="{{asset('images/emails/youtube.png')}}">
                    </a>
                    <a target="_blank" href="{{config('services.channels.pinterest')}}" class="link-none">
                        <img style="display: inline-block; margin: 0 12px; opacity: .4" width="22" src="{{asset('images/emails/pinterest.png')}}">
                    </a>
                    <a target="_blank" href="{{config('services.channels.reddit')}}" class="link-none">
                        <img style="display: inline-block; margin: 0 12px; opacity: .4" width="22" src="{{asset('images/emails/reddit.png')}}">
                    </a>
                    <a target="_blank" href="{{config('services.channels.instagram')}}" class="link-none">
                        <img style="display: inline-block; margin: 0 12px; opacity: .4" width="22" src="{{asset('images/emails/instagram.png')}}">
                    </a>
                </div>
                
                <div>
                    <p>You have received this email as a subscriber of PianoLIT.com<br>You can <a href="{{route('subscriptions.edit', $email)}}" target="_blank" class="link-none"><strong>unsubscribe</strong></a> from these emails here<br>(Don't worry, we won't take it personally).</p>
                    <p>© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')</p>
                </div>
            </div>
        @endcomponent
    @endslot
@endcomponent
