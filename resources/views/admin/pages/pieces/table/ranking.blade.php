@if($item->highlighted_at)
<span>{{$item->highlighted_at->toFormattedDateString()}}</span>
@else
<span class="text-muted"><i>Never highlighted</i></span>
@endif
{{-- <div class="d-flex">
@if($rcm = $item->getRanking('rcm'))
<div class="me-1 badge rounded-pill alert-blue"><strong>RCM {{$rcm}}</strong></div>
@endif
@if($abrsm = $item->getRanking('abrsm'))
<div class="badge rounded-pill alert-blue"><strong>ABRSM {{$abrsm}}</strong></div>
@endif
</div> --}}