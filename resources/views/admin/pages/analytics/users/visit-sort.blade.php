<form method="GET" action="{{ request()->url() }}" class="ua-filters ua-visit-sort" aria-label="Visit sorting">
    @foreach(request()->except(['visit_sort', 'visit_order', 'visits_page']) as $key => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <label>Sort visits<select name="visit_sort">
        @foreach(\App\Activity\VisitSort::OPTIONS as $value => $label)
        <option value="{{ $value }}" @if($visitSort->field === $value) selected @endif>{{ $label }}</option>
        @endforeach
    </select></label>
    <button class="ua-button ua-button-secondary" type="submit" name="visit_order" value="{{ $visitSort->order }}">Apply</button>
    <button class="ua-button ua-button-secondary ua-order-toggle" type="submit" name="visit_order" value="{{ $visitSort->reverseOrder() }}" title="{{ $visitSort->orderLabel() }}. Switch to {{ $visitSort->orderLabel($visitSort->reverseOrder()) }}" aria-label="Reverse order: {{ $visitSort->orderLabel($visitSort->reverseOrder()) }}">
        @icon($visitSort->order === 'asc' ? 'arrow-up' : 'arrow-down', ['mr' => 0])<span>Reverse order</span>
    </button>
    @if($visitSort->byActions())<span class="ua-sort-help">Actions count the colored tags.</span>@endif
</form>
