<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
                        @foreach($items as $item)
                            <p>
                                <span class="text-lg bg-blue text-white" style="padding: 1px 8px;margin-right: 4px"><strong>{{$loop->iteration}}</strong></span> 
                                {!! $item !!}
                            </p>
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>