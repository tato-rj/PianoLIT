<div class="moment-editor__row bg-light mb-2" data-moment-row>
    @if(!empty($moment['id']))<input type="hidden" data-field="id" name="moments[{{$index}}][id]" value="{{$moment['id']}}">@endif
    <div class="d-flex align-items-center justify-content-between mb-1">
        <span class="small text-muted" data-moment-number>Moment</span>
        <button type="button" class="moment-editor__delete" data-moment-action="delete" aria-label="Delete moment" title="Delete moment">@icon('trash-2', ['mr' => 0])</button>
    </div>
    <div class="row g-2">
        @foreach(['start_time' => 'Start time', 'end_time' => 'End time (optional)'] as $field => $label)
        <div class="col-6 col-lg-3">
            <span class="moment-editor__label">{{$label}}</span>
            <div class="moment-editor__time">
                <input type="text" class="form-control form-control-sm" aria-label="{{$label}}" data-field="{{$field}}" data-moment-time name="moments[{{$index}}][{{$field}}]" value="{{\App\VideoMoment::formatTimeInput($moment[$field] ?? null)}}" placeholder="MM:SS" inputmode="decimal" @if($field === 'start_time') required @endif maxlength="30">
                <div class="moment-editor__steps">
                    @foreach([1 => 'Increase', -1 => 'Decrease'] as $step => $direction)
                    <button type="button" class="moment-editor__step" data-moment-action="step" data-time-field="{{$field}}" data-time-step="{{$step}}" aria-label="{{$direction}} {{$field === 'start_time' ? 'start' : 'end'}} time by one second" title="{{$direction}} by one second">@icon($step > 0 ? 'chevron-up' : 'chevron-down', ['mr' => 0])</button>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
        <div class="col-lg-6"><label class="w-100 mb-0">Title
            <input type="text" class="form-control form-control-sm" data-field="title" name="moments[{{$index}}][title]" value="{{$moment['title'] ?? ''}}" required maxlength="255">
        </label></div>
        <div class="col-12"><label class="w-100 mb-0">Commentary
            <textarea class="form-control form-control-sm" data-field="comment" name="moments[{{$index}}][comment]" rows="2" required maxlength="2000">{{$moment['comment'] ?? ''}}</textarea>
        </label></div>
    </div>
</div>
