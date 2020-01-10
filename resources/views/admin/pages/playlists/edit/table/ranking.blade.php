<div class="text-norwap">
@if($rcm = $item->getRanking('rcm'))
<div class="badge badge-pill alert-blue"><strong>RCM {{$rcm}}</strong></div>
@endif
@if($abrsm = $item->getRanking('abrsm'))
<div class="badge badge-pill alert-blue"><strong>ABRSM {{$abrsm}}</strong></div>
@endif
</div>