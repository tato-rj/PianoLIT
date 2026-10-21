<div class="row g-3">
    <div class="col-md-8">
        @input(['bag' => $bag, 'id' => $prefix.'-name', 'name' => 'name', 'label' => 'Full name', 'value' => optional($composer)->name, 'limit' => 140])
    </div>
    <div class="col-md-4">
        @select(['bag' => $bag, 'id' => $prefix.'-gender', 'name' => 'gender', 'label' => 'Gender', 'placeholder' => 'Choose a gender', 'choices' => ['male' => 'Male', 'female' => 'Female'], 'value' => optional($composer)->gender, 'required' => $composer ? false : 'required'])
    </div>
    <div class="col-md-6">
        @include('components.form.upload.file', ['bag' => $bag, 'id' => $prefix.'-cover', 'name' => 'cover_image', 'label' => $composer ? 'Replace portrait (optional)' : 'Portrait', 'accept' => 'image/*', 'required' => !$composer])
    </div>
    <div class="col-md-6">
        @select(['bag' => $bag, 'id' => $prefix.'-period', 'name' => 'period', 'label' => 'Period', 'placeholder' => 'Choose a period', 'choices' => \App\Tag::periods()->get()->mapWithKeys(function ($period) { return [$period->name => ucfirst($period->name)]; })->all(), 'value' => strtolower(optional($composer)->period ?? ''), 'required' => $composer ? false : 'required'])
    </div>
    <div class="col-md-6">
        @select(['bag' => $bag, 'id' => $prefix.'-country', 'name' => 'country_id', 'label' => 'Nationality', 'placeholder' => 'Choose a nationality', 'choices' => $countries->pluck('nationality', 'id')->all(), 'value' => optional($composer)->country_id, 'required' => $composer ? false : 'required'])
    </div>
    <div class="col-md-6">
        @select(['bag' => $bag, 'id' => $prefix.'-ethnicity', 'name' => 'ethnicity', 'label' => 'Ethnicity (optional)', 'choices' => ['' => 'Not set'] + collect(ethnicities())->mapWithKeys(function ($value) { return [$value => ucfirst($value)]; })->all(), 'value' => optional($composer)->ethnicity, 'required' => false])
    </div>
    <div class="col-md-6">
        @input(['bag' => $bag, 'id' => 'born-in', 'name' => 'date_of_birth', 'label' => 'Date of birth (optional)', 'placeholder' => 'MM/DD/YYYY', 'value' => optional(optional($composer)->date_of_birth)->format('m/d/Y'), 'required' => false])
    </div>
    <div class="col-md-6">
        @input(['bag' => $bag, 'id' => 'died-in', 'name' => 'date_of_death', 'label' => 'Date of death (optional)', 'placeholder' => 'MM/DD/YYYY', 'value' => optional(optional($composer)->date_of_death)->format('m/d/Y'), 'required' => false])
    </div>
</div>
