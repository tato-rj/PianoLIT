@component('components.modal', ['id' => 'add-modal', 'header' => 'New composer'])
@slot('body')
<form method="POST" action="{{route('admin.composers.store')}}" enctype="multipart/form-data">
  @csrf
  {{-- Name --}}
  <div class="d-flex">
    <div class="flex-grow mr-2">
      @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Full name', 'limit' => 120])
    </div>
    <div>
      @include('admin.pages.composers.gender', ['gender' => old('gender')])
    </div>
  </div>
  @textarea(['bag' => 'default', 'name' => 'biography', 'placeholder' => 'Life\'s summary', 'limit' => 255, 'rows' => 6])
  @textarea(['bag' => 'default', 'name' => 'curiosity', 'placeholder' => 'Did you know?', 'limit' => 125, 'rows' => 3, 'required' => false])

  <div class="form-row">
    @file(['bag' => 'default', 'name' => 'cover_image', 'placeholder' => 'Cover image', 'grid' => 'col'])
    @select(['bag' => 'default', 'name' => 'period', 'placeholder' => 'Period', 'options' => \App\Tag::periods()->get()->pluck('name'), 'grid' => 'col'])
  </div>

  <div class="form-row">
    @select(['bag' => 'default', 'name' => 'country_id', 'placeholder' => 'Nationality', 'options' => $countries->pluck('id', 'nationality'), 'grid' => 'col'])
    @select(['bag' => 'default', 'name' => 'ethnicity', 'placeholder' => 'Ethnicity', 'options' => ethnicities(), 'grid' => 'col'])
  </div>

  <div class="form-row">
    @input(['bag' => 'default', 'name' => 'date_of_birth', 'id' => 'born-in', 'placeholder' => 'Date born', 'grid' => 'col'])
    @input(['bag' => 'default', 'name' => 'date_of_death', 'id' => 'died-in', 'placeholder' => 'Date died', 'grid' => 'col'])
  </div>
  @options(['bag' => 'default', 'required' => false, 'nogutters' => true, 'type' => 'checkbox', 'name' => 'is_famous', 'options' => ['Is this composer famous?' => true]])
  @options(['bag' => 'default', 'required' => false, 'type' => 'checkbox', 'name' => 'is_pedagogical', 'options' => ['Is this mostly a pedagogical composer?' => true]])

  @submit(['label' => 'Add composer', 'block' => true])
</form>
@endslot
@endcomponent
