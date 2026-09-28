@auth
@if(auth()->user()->purchasesOf($infograph)->exists())
<div class="text-center">
	<a href="{{route('users.purchases')}}" class="btn btn-block btn-green">@icon('cloud-download')Download it again</a>
	<p class="text-muted"><small>Downloaded on {{auth()->user()->purchasesOf($infograph)->first()->created_at->toFormattedDateString()}}</small></p>
</div>
@else
<form method="GET" action="{{route('infographs.download', $infograph)}}" disable-on-submit>
	<button type="submit" class="btn btn-block btn-green py-2 font-weight-bold">@icon('file-down')Download</button>
</form>
@endif
@else
<a id="auth-only" href="" class="btn btn-block btn-green py-2 font-weight-bold">@icon('file-down', ['mr' => 2])Download</a>
@endauth