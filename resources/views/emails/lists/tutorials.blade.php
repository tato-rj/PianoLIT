@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<img src="{{asset('images/emails/tutorials-cover.jpg')}}" style="width: 100%">
<div style="border: 1px solid black; padding: 20px">
<p>Here is quick recap of the tutorials we published this week! If you need help with any piece found in our app, just tap the "Request Tutrial" button on the videos tab to submit your request.</p>

@foreach(tutorials() as $tutorial)
@component('mail::panel')
<div style="text-align: center; border-bottom: 1px solid #d0d6dd; margin-bottom: 8px; padding-bottom: 8px"><strong><small>{{$tutorial['piece']}}</small></strong></div>
<h2 style="margin: 0">{{$tutorial['title']}}</h2>
{{$tutorial['description']}}

<a href="{{config('app.stores.ios')}}" class="button button-primary" style="display: block; font-size: .9rem;" target="_blank">Open the app</a>
@endcomponent
@endforeach
</div>

</div>

If you have any suggestions or special requests, don't hesitate to send us a note. We'll love to hear from you!

Best,<br>
Elena from {{ config('app.name') }}
@endcomponent
