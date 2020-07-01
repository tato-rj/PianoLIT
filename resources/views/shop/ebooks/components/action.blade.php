@if($ebook->isFree())
<a href="#" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'cloud-download-alt'])Download now</a>
@else
<a href="{{route('ebooks.checkout', $ebook)}}" class="btn btn-sm btn-wide btn-primary mb-2">@fa(['icon' => 'shopping-cart'])Buy now</a>
@endif