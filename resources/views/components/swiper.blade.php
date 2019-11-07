<section class="mb-6 result-row">
    <div class="">
        <h5 class="mb-3">
        	@if($title[1])
        	<strong class="text-grey">{{$title[0]}} <span class="tag"><u><a href="{{route('search.index', ['global', 'search' => $title[1]])}}" class="text-muted">{{$title[1]}}</a></u></span></strong>
        	@else
        	<strong class="text-grey">{{$title[0]}}</strong>
        	@endif
        </h5>
    </div>
    <div class="pb-2 w-100 d-flex custom-scroll dragscroll" style="overflow-x: scroll;">
        {{$slot}}
    </div>    
</section>