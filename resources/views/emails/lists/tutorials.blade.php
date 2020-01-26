@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 style="color: #3D4852;
    font-size: 34px;
    font-weight: bold;
    margin-top: -34px;
    margin-bottom: 16px;
    text-align: center;
    ">This week's tutorials & tips</h1>

Here is quick recap of the tutorials we published this week! If you need help with any piece found in our app, just tap the <span class="text-blue"><strong>Request Tutorial</strong></span> button on the videos tab to submit your request.

<div class="divider divider-vertical"></div>

@foreach(tutorials() as $tutorial)
@component('mail::panel')
<div style="text-align: center; border-bottom: 1px solid #d0d6dd; margin-bottom: 8px; padding-bottom: 8px; font-size: 16px"><strong><small>{{$tutorial['piece']}}</small></strong></div>
<h1 style="text-align: center; margin-bottom: 8px">{{$tutorial['title']}}</h1>
<div style="display: block; margin: 0 auto; text-align: center;">
<div class="badge badge-pill bg-intermediate" style=" margin-bottom: 8px" data-test="{{json_encode($tutorial)}}">intermediate</div>
</div>
{{$tutorial['description']}}

{{-- <a href="{{config('app.stores.ios')}}" class="button button-primary" style="display: block; font-size: .9rem;" target="_blank">Watch video in the app</a> --}}
@endcomponent
@endforeach

<div class="divider divider-vertical"></div>

<h1 style="font-size: 24px;">How can I find these videos in the app?</h1>
<p><span style="font-size: 20px; margin-right: 4px"><strong>1</strong></span> Open the <strong class="text-blue">app</strong></p>
<p><span style="font-size: 20px; margin-right: 4px"><strong>2</strong></span> Search for the piece on the <strong class="text-blue">search</strong> tab</p>
<p><span style="font-size: 20px; margin-right: 4px"><strong>3</strong></span> You'll find them under the <strong class="text-blue">video</strong> tab</p>
<p><span style="font-size: 20px; margin-right: 4px"><strong>4</strong></span> Enjoy!</p>

<div class="divider divider-vertical"></div>

If you have any suggestions or special requests, don't hesitate to send us a note. We'll love to hear from you!

Best,<br>
Elena from {{ config('app.name') }}
@endcomponent
