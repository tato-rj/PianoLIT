<section class="container">
	<nav aria-label="breadcrumb">
	  <ol class="breadcrumb py-0">
	  	@foreach($pages as $page => $route)
	  	@if($loop->last)
	    <li class="breadcrumb-item active" aria-current="page">{{ucfirst($page)}}</li>
	  	@else
	    <li class="breadcrumb-item"><a class="link-blue" href="{{$route}}">{{ucfirst($page)}}</a></li>
	  	@endif
	  	@endforeach
	  </ol>
	</nav>
</section>