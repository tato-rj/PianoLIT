<div class="d-flex flex-wrap gap-4">
    <div class="d-flex align-items-center gap-2">
        <input type="hidden" name="is_famous" value="0">
        @toggle(['toggle' => (bool) old('is_famous', optional($composer)->is_famous ?? false), 'name' => 'is_famous', 'inputValue' => '1', 'ariaLabel' => 'Famous composer'])
        <span>Famous composer</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <input type="hidden" name="is_pedagogical" value="0">
        @toggle(['toggle' => (bool) old('is_pedagogical', optional($composer)->is_pedagogical ?? false), 'name' => 'is_pedagogical', 'inputValue' => '1', 'ariaLabel' => 'Primarily pedagogical'])
        <span>Primarily pedagogical</span>
    </div>
</div>
