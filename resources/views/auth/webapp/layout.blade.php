@extends('layouts.raw', ['title' => 'PianoLIT WebApp', 
    'shareable' => [
        'keywords' => '',
        'title' => 'PianoLIT WebApp',
        'description' => 'Discover new pieces and find inspiration to play only what you love.',
        'thumbnail' => asset('images/webapp/thumbnail.jpg'),
        'created_at' => carbon('5/28/2020')->format(DateTime::ISO8601),
        'updated_at' => carbon('5/28/2020')->format(DateTime::ISO8601)
    ]])

@push('header')
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '208256284230812');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=208256284230812&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
<style type="text/css">
.hidden-action {
    display: none; 
}

#action > div {
    -webkit-box-shadow: 0 5px 30px rgba(0,0,0,.1);
    box-shadow: 0 5px 30px rgba(0,0,0,.1);
}

.carousel-indicators li {
    background-color: #b8c2cc;
}

.carousel-indicators li.active {
    background-color: #0055fe;
}
</style>
@endpush

@section('content')
<div class="container-fluid bg-light">
    <div class="row h-100vh">
        <div id="action" class="col-lg-5 col-md-12 col-12 mx-auto p-3 {{$animated ? 'hidden-action' : null}}">
            <div class="d-flex flex-center h-100 bg-white p-4">
                <div style="max-width: 400px" class="{{$classes ?? null}}">
                	<a class="navbar-brand mb-2" href="{{config('app.url')}}">
                		<img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
                	</a>
                    <h3>{{$title}}</h3>
                    <p>{!! $subtitle !!}</p>
                    
                    {{$slot}}

                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 col-12 mx-auto">
            <div class="d-flex flex-center h-100">
                <div class="py-4">

                    @include('auth.webapp.carousel')

                    <div class="text-center" style="display: {{$animated ? 'block' : 'none'}}">
                        <button id="get-started" class="btn btn-default btn-wide shadow rounded-pill">GET STARTED</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$('button#get-started').click(function() {
    $('#action').slideToggle();
    $(this).hide();
});

$('#onboarding').carousel({
  interval: 3000
});
</script>
@endpush