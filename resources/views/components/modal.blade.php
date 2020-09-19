<div class="modal fade" id="{{$id}}">
  <div class="modal-dialog modal-{{$size ?? null}}">
    <div class="modal-content border-0">
      <div class="modal-header bg-{{$headerBg ?? null}} {{!empty($headerNoborder) ? 'border-0' : null}}">
        @isset($title)
        <h5 class="modal-title">{!! $title !!}</h5>
        @endisset
        @isset($titleRaw)
        <div class="w-100">{!! $titleRaw !!}</div>
        @endisset
        <button class="close text-{{$closeColor ?? null}} {{!empty($titleRaw) ? 'absolute-top-right' : null}}" type="button" data-dismiss="modal">
          <span>×</span>
        </button>
      </div>
      <div class="modal-body {{!empty($bodyNoPadding) ? 'p-0' : null}}">
      {!! $body !!}
      </div>
      @isset($footer)
      <div class="modal-footer">
      {!! $footer !!}
      </div>
      @endisset
      @isset($footerRaw)
      <div class="w-100">{!! $footerRaw !!}</div>
      @endisset
    </div>
  </div>
</div>