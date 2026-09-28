{{$item->title}}
@if($item->hasGift())
<span class="ml-2 gift position-relative">@icon('gift', ['mr' => 0, 'styles' => 'color: #E92C59'])</span>
@endif