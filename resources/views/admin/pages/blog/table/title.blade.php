{{$item->title}}
@if($item->hasGift())
<span class="ms-2 gift position-relative">@icon('gift', ['mr' => 0, 'styles' => 'color: #E92C59'])</span>
@endif