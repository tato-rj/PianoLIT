<div class="accordion mb-4" id="accordionExample">
  @foreach($purchases as $purchase)
  <div class="mb-2 pb-2 {{$loop->last ? null : 'border-bottom'}}">
    <div class="p-2 cursor-pointer" data-toggle="collapse" data-target="#purchase-{{$purchase->id}}">
      <div class="d-flex align-items-end">
        <div class="mr-4">
          {{$purchase->created_at->toFormattedDateString()}}
        </div>
        <div class="flex-grow">
          <div><span class="badge badge-pill badge-primary mr-2">{{$purchase->type}}</span></div>
          <strong class="mr-2">{{$purchase->item->title}}</strong>
        </div>
        <div>@fa(['icon' => 'caret-down'])</div>
      </div>
    </div>

    <div id="purchase-{{$purchase->id}}" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
      <div class="py-2 px-3 bg-light rounded-pill">
        <div class="d-flex d-apart ">
          <div class="d-flex">
            <div class="mr-2"><strong>Cost</strong></div>
            <div>
              @if($purchase->item->isFree())
              <strong class="text-green">FREE</strong>
              @else
              ${{$purchase->item->finalPrice()}}
              @endif 
            </div>
          </div>
          <div class="d-flex">
            <div class="mr-2"><strong>Download</strong></div>
            <div>
              @foreach($purchase->item->links() as $label => $hash)
              <a href="{{route('shop.download', ['purchase' => $purchase, 'path' => $hash])}}">
                @fa(['icon' => 'cloud-download-alt', 'mr' => 1]){{$label}}
              </a> {{$loop->count > 1 && ! $loop->last ? ' | ' : null}}
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>