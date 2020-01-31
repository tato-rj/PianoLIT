<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
						@foreach($tutorials as $tutorial)
						@include('mail::badge', ['color' => 'grey', 'text' => strtoupper($tutorial['title'])])

						<p style="margin-left: 4px">{{$tutorial['description']}}</p>
						@endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
