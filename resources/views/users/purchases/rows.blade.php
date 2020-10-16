@foreach($purchases as $purchase)
<div json="{{$purchase->id}}"></div>
{{--     @if($purchase)
      <tr>
        <td class="text-nowrap">{{$purchase->created_at->toFormattedDateString()}}</td>
        <td>{{$purchase->type}}</td>
        <td class="text-nowrap">{{$purchase->item->title}}
        @if($purchase->isFree)
        <span class="badge badge-pill alert-green">FREE</span>
        @endif
        </td>
        <td class="text-nowrap">
            @foreach($purchase->item->links() as $label => $hash)
            <a href="{{route('shop.download', ['purchase' => $purchase, 'path' => $hash])}}">{{$label}}</a> {{$loop->count > 1 && ! $loop->last ? ' | ' : null}}
            @endforeach
        </td>
      </tr>
    @endif --}}
@endforeach