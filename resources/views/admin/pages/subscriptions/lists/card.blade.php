      <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
        <div class="rounded border p-3 h-100 d-flex flex-column justify-content-between">
          <div class="mb-3">
            <h5 class="m-0"><strong>{{$list->name}}</strong></h5>
            <p class="m-0"><small>{{$list->description}}</small></p>
            @if($list->last_sent_at)
            <p class="m-0 text-success"><small><i class="fas fa-calendar-day"></i> last sent {{$list->last_sent_at->diffForHumans()}}</small></p>
            @endif
          </div>
          <div>
            <p><i class="fas fa-users"></i> {{$list->subscribers_count}} {{str_plural('subscriber', $list->subscribers_count)}}</p>
            <div class="d-flex d-apart mb-2">
              <div class="d-flex">
                <a href="{{route('admin.subscriptions.lists.edit', $list)}}" class="btn btn-default btn-sm px-3 mr-2">Edit</a>
                @if(view()->exists($list->actions_view))
                @include($list->actions_view)
                @endif
               </div>
              <div>
                <a href="#" data-url="{{route('admin.subscriptions.lists.destroy', $list)}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="delete text-danger">
                  <i class="far fa-trash-alt align-middle"></i>
                </a>
              </div>
            </div>
            <div>
              <form method="GET" action="{{route('admin.subscriptions.export')}}" target="_blank" id="export-form">
                @csrf
                <input type="hidden" name="type" value="txt">
                <input type="hidden" name="list_id" value="{{$list->id}}">
                <button type="submit" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-file-alt mr-2"></i>Export emails</button>
              </form>
            </div>
          </div>
        </div>
      </div>