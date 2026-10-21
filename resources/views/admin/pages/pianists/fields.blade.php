@input(['bag' => $bag, 'id' => $prefix.'-name', 'name' => 'name', 'label' => 'Full name', 'value' => optional($pianist)->name, 'limit' => 140])
@textarea(['bag' => $bag, 'id' => $prefix.'-biography', 'name' => 'biography', 'label' => 'Biography', 'value' => optional($pianist)->biography, 'rows' => 5])
@select(['bag' => $bag, 'id' => $prefix.'-country', 'name' => 'country_id', 'label' => 'Nationality', 'choices' => ['' => 'Choose a nationality'] + $countries->pluck('nationality', 'id')->all(), 'value' => optional($pianist)->country_id])
<div class="row g-3">
    @input(['bag' => $bag, 'id' => $prefix.'-born', 'name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'value' => optional(optional($pianist)->date_of_birth)->format('Y-m-d'), 'grid' => 'col-sm-6'])
    @input(['bag' => $bag, 'id' => $prefix.'-died', 'name' => 'date_of_death', 'label' => 'Date of death (optional)', 'type' => 'date', 'value' => optional(optional($pianist)->date_of_death)->format('Y-m-d'), 'required' => false, 'grid' => 'col-sm-6'])
</div>
@input(['bag' => $bag, 'id' => $prefix.'-itunes', 'name' => 'itunes_id', 'label' => 'Apple Music artist ID (optional)', 'placeholder' => 'e.g. 123456789', 'value' => optional($pianist)->itunes_id, 'required' => false, 'limit' => 255, 'describedBy' => $prefix.'-itunes-note'])
<p class="small text-muted mb-0" id="{{ $prefix }}-itunes-note">The number at the end of their Apple Music artist link. Leave blank to hide albums.</p>
