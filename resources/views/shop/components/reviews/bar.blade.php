@php($count = $product->publishedReviews()->byRating($i)->count())
<div class="d-flex mb-2">
	<div class="lead text-uppercase text-nowrap pr-2" style="font-size: 88%; width: 52px">{{$i}} {{str_plural('star', $i)}}</div>
	<div class="flex-grow bg-light position-relative">
		<div class="bg-primary h-100 position-absolute" style="width: {{percentage($count, $product->publishedReviews()->count())}}%; left: 0; top: 0"></div>
	</div>
	<div style="font-size: 88%" class="pl-2">{{$count}}</div>
</div>