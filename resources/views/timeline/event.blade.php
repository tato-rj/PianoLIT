<div class="px-4 pt-3 d-flex align-items-baseline">
	<a href="{{$event['url'] ? $event['url'] : null}}" target="_blank" class="link-none">
	  <div style="white-space: nowrap;" class="rounded px-2 py-1 mr-2 alert-{{$event['color']}} hover-shadow">
	  	<strong><i class="fas fa-{{$event['icon']}} mr-2"></i>{{$event['year']}}</strong>
	  </div>
	</a>
  <div>{{$event['event']}}</div>
</div>