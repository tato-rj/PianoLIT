@component('mail::layout-simple')
    {{-- Body --}}
    {{ $slot }}

    {{-- Footer --}}
    @slot('footer')
        <div>
            @if(! empty($cancel_url))
            <form method="POST" action="{{$cancel_url}}" target="_blank">
                @csrf
                @method('DELETE')
                If you wish to stop receiving these emails <button type="submit" style="padding: 0; border: 0; background: transparent; color: grey; cursor: pointer;"><strong>click here</strong></button> you can update your preferences or email your request to contact@pianolit.com.
            </form>
            @endif
        </div>
    @endslot
@endcomponent
