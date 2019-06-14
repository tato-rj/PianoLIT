<div class="modal fade" id="modal-{{str_slug($title)}}" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-{{$size ?? null}} border-0" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{$title}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body px-4 pb-0">
        {{$slot}}
      </div>
      @if(!empty($feedback))
      <div class="text-center p-3">
        <p class="mb-1 border-top pt-3">Was this helpful?</p>
        <div class="d-flex justify-content-center align-content-center">
          <button class="border-0 bg-transparent mx-1" title="Yes!"><i class="far text-grey fa-thumbs-up"></i></button>
          <button class="border-0 bg-transparent mx-1" title="Not so much..."><i class="far text-grey fa-thumbs-down"></i></button>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>