@component('mail::raw')
<h1>EMAIL: {{$email}}</h1>


<div>

    <form method="POST" action="{{$email}}" target="_blank">
        @csrf
        @method('DELETE')
        If you wish to stop receiving these emails <button type="submit" style="padding: 0; border: 0; background: transparent; color: grey; cursor: pointer;"><strong>click here</strong></button> you can update your preferences or email your request to contact@pianolit.com.
    </form>

</div>
@endcomponent
