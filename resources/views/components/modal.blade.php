<div class="modal fade" id="{{$id}}">
  <div class="modal-dialog modal-{{array_find($options ?? null, ['size'])}}">
    <div class="modal-content border-0">
      <div class="modal-header 
        bg-{{array_find($options ?? null, ['header', 'background'])}} 
        {{array_find($options ?? null, ['header', 'border']) == true ? null : 'border-0'}}" style="{{array_find($options ?? null, ['header', 'show']) === false ? 'display:none' : null}}">
        @isset($header)
          @if(array_find($options ?? null, ['header', 'raw']))
          <div class="w-100">{!! $header !!}</div>
          @else
          <h5 class="modal-title clamp-1">{!! $header !!}</h5>
          @endif
        @endisset
      <button class="close text-{{array_find($options ?? null, ['header', 'close', 'color'])}} {{array_find($options ?? null, ['header', 'close', 'position'])}}" type="button" data-dismiss="modal">
          @fa(['icon' => 'times', 'mr' => 0])
        </button>
      </div>
      <div class="modal-body p-{{array_find($options ?? null, ['body', 'padding'])}}">
      {!! $body !!}
      </div>
      @isset($footer)
      <div class="{{array_find($options ?? null, ['footer', 'raw']) == true ? 'w-100' : 'modal-footer'}} {{array_find($options ?? null, ['footer', 'border']) == true ? null : 'border-0'}}">
      {!! $footer !!}
      </div>
      @endisset
    </div>
  </div>
</div>