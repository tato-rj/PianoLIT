<table width="100%" cellpadding="0" cellspacing="0" class="mb-5">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
						<div style="width: 100%; min-height: 140px; border-radius: 15px 50px; background: url({{$piece->getBackground()}}); background-position: center; background-size: cover; position: relative;" class="mb-3"></div>
                        <div>
                            <img src="{{$piece->composer->cover_image}}" width="98" style="border-radius: 60px; display: inline-block; margin-right: 12px; vertical-align: middle">
                            <div style="display: inline-block; vertical-align: middle; max-width: 70%;">
                                <p class="m-0 text-sm">
                                <span class="dot bg-{{$piece->level->name}}"></span> {{strtoupper($piece->extended_level_name)}}
                                </p>
                                <h1 class="m-0 text-lg">{{$piece->medium_name}}</h1>
                                <p class="mb-1">by {{$piece->composer->name}}</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
