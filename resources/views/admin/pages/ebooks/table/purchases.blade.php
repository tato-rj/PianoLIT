{{-- <button class="btn-raw" data-toggle="modal" data-target="#purchases-{{$item->id}}-modal">{{$item->purchases_count}}</button>

@component('components.modal', ['id' => 'purchases-'.$item->id.'-modal'])
@slot('body')
@foreach($item->purchases()->with('user')->get() as $purchase)
<div class="text-muted"><small>Purchased at {{$purchase->created_at->toFormattedDateString()}}</small></div>
<p>
	<strong>({{$purchase->user->id}})</strong> 
	{{$purchase->user->full_name}}
	@fa(['icon' => 'money-bill-wave', 'mr' => 0, 'color' => $purchase->cost ? 'green' : 'grey'])</p>
@endforeach
@endslot
@endcomponent --}}