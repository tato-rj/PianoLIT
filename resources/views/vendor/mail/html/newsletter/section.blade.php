<table class="pt-2 pb-2" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
                        <a href="{{$badge['link']}}" target="_blank"><div class="badge-lg badge-{{$badge['color']}} mb-3">{{strtoupper($badge['name'])}}</div></a>
                        <h1 class="text-lg"><strong>{{$title}}</strong></h1>
                        <p>{{Illuminate\Mail\Markdown::parse($slot)}}</p>

                        @include('mail::divider', ['orientation' => 'horizontal'])
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>