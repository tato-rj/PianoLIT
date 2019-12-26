@component('mail::message')
# Hi {{$request->user->first_name}}

The tutorial for <u>{{$request->piece->medium_name_with_composer}}</u> is ready! Just go to the app, open this piece and you will see it under the videos tab. Enjoy!{{emoji('happy', 1)[0]}}

If you have any comments, suggestions or specific requests reguarding this tutorial, just send us a message to <a href="mailto:contact@pianolit.com">contact@pianolit.com</a>. Your feedback will help us improve our videos for all of our awesome users.

Thanks,<br>
Elena from {{ config('app.name') }}
@endcomponent
