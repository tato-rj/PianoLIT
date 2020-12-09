<h4 class="clamp-2 {{$product->author ? 'mb-0' : null}}"><strong>{{$product->title}}</strong></h4>
@if($product->author)
<p class="clamp-2 text-muted"><i>by {{$product->author}}</i></p>
@endif