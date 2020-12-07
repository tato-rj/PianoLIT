<h4 class="mb-1 clamp-2"><strong>{{$escore->title}}</strong></h4>
@if($escore->pieces()->exists())
<p class="clamp-2 text-muted"><i>by {{$escore->piece->composer->name}}</i></p>
@endif