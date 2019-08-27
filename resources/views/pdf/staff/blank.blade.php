<style type="text/css">
	.page-break {
		page-break-after: always;
	}

	img {
		width: 100%;
		margin: 1px 0;
	}
</style>

@if(request('type') == 'piano')
<div>
	<div style="margin-top: 32px; position: relative; padding: 0 60px">
		@for($q=0; $q<6; $q++)
		<div style="margin-bottom: 28px">
			<img src="{{asset('images/sheets/piano.png')}}" style="width: 100%">
		</div>
		@endfor
	</div>
</div>
@elseif(request('type') == 'blank')
<div>
	<div style="margin-top: 50px; position: relative; padding: 0 60px">
		@for($q=0; $q<12; $q++)
		<img src="{{asset('images/sheets/blank.svg')}}" style="width: 100%; margin-bottom: 40px">
		@endfor
	</div>
</div>
@endif