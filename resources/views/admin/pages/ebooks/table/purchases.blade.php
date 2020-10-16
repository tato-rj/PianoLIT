<button class="btn-raw" data-toggle="modal" data-target="#purchases-modal">{{$item->purchases_count}}</button>

@component('components.modal', ['id' => 'purchases-modal'])
@slot('body')
@foreach($item->purchases()->with('user')->get() as $purchase)
<div class="text-muted"><small>Purchased at {{$purchase->created_at->toFormattedDateString()}}</small></div>
<p><strong>({{$purchase->user->id}})</strong> {{$purchase->user->full_name}}</p>
@endforeach
@endslot
@endcomponent