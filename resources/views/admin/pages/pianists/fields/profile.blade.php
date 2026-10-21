@input(['bag' => $bag, 'id' => $prefix.'-name', 'name' => 'name', 'label' => 'Full name', 'value' => array_key_exists('name', session()->getOldInput()) ? null : optional($pianist)->name, 'limit' => 140])
@if($includeBiography ?? false)@include('admin.pages.pianists.fields.biography')@endif
@select(['bag' => $bag, 'id' => $prefix.'-country', 'name' => 'country_id', 'label' => 'Nationality', 'choices' => ['' => 'Choose a nationality'] + $countries->pluck('nationality', 'id')->all(), 'value' => array_key_exists('country_id', session()->getOldInput()) ? null : optional($pianist)->country_id])
<div class="row g-3">
    @input(['bag' => $bag, 'id' => $prefix.'-born', 'name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'value' => array_key_exists('date_of_birth', session()->getOldInput()) ? null : optional(optional($pianist)->date_of_birth)->format('Y-m-d'), 'grid' => 'col-sm-6'])
    @input(['bag' => $bag, 'id' => $prefix.'-died', 'name' => 'date_of_death', 'label' => 'Date of death (optional)', 'type' => 'date', 'value' => array_key_exists('date_of_death', session()->getOldInput()) ? null : optional(optional($pianist)->date_of_death)->format('Y-m-d'), 'required' => false, 'grid' => 'col-sm-6'])
</div>
