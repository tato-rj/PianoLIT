@auth
@if(auth()->user()->purchasesOf($infograph)->exists())
<div class="text-center">
	<a href="{{route('users.purchases')}}" class="btn btn-block btn-green">@fa(['icon' => 'cloud-download-alt'])Download it again</a>
	<p class="text-muted"><small>Downloaded on {{auth()->user()->purchasesOf($infograph)->first()->created_at->toFormattedDateString()}}</small></p>
</div>
@else
<form method="GET" action="{{route('infographs.download', $infograph)}}" disable-on-submit>
	<button type="submit" class="btn btn-block btn-green py-2 font-weight-bold">@fa(['icon' => 'file-download'])Download</button>
</form>
@endif
@else
<a id="auth-only" href="" class="btn btn-block btn-green py-2 font-weight-bold"><i class="fas fa-file-download mr-2"></i>Download</a>
@endauth