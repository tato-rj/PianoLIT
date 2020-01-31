<table style="margin: 0 auto; margin-bottom: 42px" width="330" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
                        @foreach($items as $item)
                            <p style="margin-bottom: 6px">{{hex('check')}} <strong>{{$item}}</strong></p>
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>