<div class="rounded bg-light p-3 mb-3" data-moment-row>
    @if(!empty($moment['id']))<input type="hidden" data-field="id" name="moments[{{$index}}][id]" value="{{$moment['id']}}">@endif
    <div class="row g-2">
        <div class="col-sm-6"><label class="w-100">Start time
            <input type="text" class="form-control" data-field="start_time" name="moments[{{$index}}][start_time]" value="{{$moment['start_time'] ?? ''}}" placeholder="0:12" required maxlength="30">
        </label></div>
        <div class="col-sm-6"><label class="w-100">End time (optional)
            <input type="text" class="form-control" data-field="end_time" name="moments[{{$index}}][end_time]" value="{{$moment['end_time'] ?? ''}}" placeholder="0:19.5" maxlength="30">
        </label></div>
    </div>
    <label class="w-100 mt-2">Title
        <input type="text" class="form-control" data-field="title" name="moments[{{$index}}][title]" value="{{$moment['title'] ?? ''}}" required maxlength="255">
    </label>
    <label class="w-100 mt-2">Commentary
        <textarea class="form-control" data-field="comment" name="moments[{{$index}}][comment]" rows="3" required maxlength="2000">{{$moment['comment'] ?? ''}}</textarea>
    </label>
    <div class="d-flex gap-2 mt-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-moment-action="up" aria-label="Move moment up">Move up</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-moment-action="down" aria-label="Move moment down">Move down</button>
        <button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-moment-action="delete">Delete moment</button>
    </div>
</div>
