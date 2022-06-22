<table width="100%" cellpadding="0" cellspacing="0" class="mb-5">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="panel-item">
						<div style="position: relative; width: 100%; min-height: 140px; border-radius: 15px 50px; background: url({{$piece->getBackground()}}); background-position: center; background-size: cover; position: relative;" class="mb-3">
							<div style="position: absolute; bottom: -36px; left: 10px;">
                                <img src="{{$piece->composer->cover_image}}" width="102" style="border-radius: 60px; border: 4px solid white">
							</div>
						</div>
                        <div style="margin-left: 120px">
                            <p class="m-0 text-sm">
                            <span class="dot bg-{{$piece->level->name}}"></span> {{strtoupper($piece->extended_level_name)}}
                            </p>
                            <h1 class="m-0 text-lg">{{$piece->medium_name}}</h1>
                            <p class="mb-1">by {{$piece->composer->name}}</p>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
