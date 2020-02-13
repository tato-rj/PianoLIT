<table class="pt-4 pb-4" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
                        <div class="badge-lg badge-orange mb-3">{{strtoupper($badge['name'])}}</div>
                        <h1 class="text-lg"><strong>{{$title}}</strong></h1>
                        <p>{{Illuminate\Mail\Markdown::parse($slot)}}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>