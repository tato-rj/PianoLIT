@if($item->highlighted_at)
<span>Already highlighted</span>
{{-- <span class="text-green">{{$item->highlighted_at->toFormattedDateString()}}</span> --}}
@else
<span class="text-muted"><i>Never highlighted</i></span>
@endif
{{-- <div class="d-flex">
@if($rcm = $item->getRanking('rcm'))
<div class="mr-1 badge badge-pill alert-blue"><strong>RCM {{$rcm}}</strong></div>
@endif
@if($abrsm = $item->getRanking('abrsm'))
<div class="badge badge-pill alert-blue"><strong>ABRSM {{$abrsm}}</strong></div>
@endif
</div> --}}