<form method="GET" action="{{ request()->url() }}" class="ua-filters ua-visit-sort" aria-label="Visit sorting">
    @foreach(request()->except(['visit_sort', 'visits_page']) as $key => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <label>Sort visits<select name="visit_sort">
        @foreach(\App\Activity\VisitSort::OPTIONS as $value => $label)
        <option value="{{ $value }}" @if($visitSort->value === $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    <button class="ua-button ua-button-secondary" type="submit">Apply</button>
    @if($visitSort->byActions())<span class="ua-sort-help">Actions count the colored tags.</span>@endif
</form>
