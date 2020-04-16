<div class="modal fade" id="{{$id}}">
  <div class="modal-dialog modal-{{$size ?? null}}">
    <div class="modal-content">
      <div class="modal-header">
        @isset($title)
        <h5 class="modal-title" id="logoutModalLabel">{{$title}}</h5>
        @endisset
        <button class="close" type="button" data-dismiss="modal">
          <span>×</span>
        </button>
      </div>
      <div class="modal-body">
      {!! $body !!}
      </div>
      @isset($footer)
      <div class="modal-footer">
      {!! $footer !!}
      </div>
      @endisset
    </div>
  </div>
</div>