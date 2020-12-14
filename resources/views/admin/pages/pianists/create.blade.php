@component('components.modal', ['id' => 'add-modal', 'header' => 'New composer'])
@slot('body')
<form method="POST" action="{{route('admin.pianists.store')}}" enctype="multipart/form-data">
  @csrf
  @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Full name', 'limit' => 120])
  @textarea(['bag' => 'default', 'name' => 'biography', 'placeholder' => 'Life\'s summary', 'limit' => 255, 'rows' => 6])
  @include('admin.components.link', ['link' => 'https://linkmaker.itunes.apple.com/en-us'])
  @input(['bag' => 'default', 'name' => 'iteuns_id', 'placeholder' => 'iTunes ID'])
  <div class="form-row">
    @select(['bag' => 'default', 'name' => 'country_id', 'placeholder' => 'Nationality', 'options' => $countries->pluck('id', 'nationality'), 'grid' => 'col'])
    @file(['bag' => 'default', 'name' => 'cover', 'placeholder' => 'Cover image', 'grid' => 'col'])
  </div>
  <div class="form-row">
    @input(['bag' => 'default', 'name' => 'date_of_birth', 'id' => 'born-in', 'placeholder' => 'Date born', 'grid' => 'col'])
    @input(['bag' => 'default', 'name' => 'date_of_death', 'id' => 'died-in', 'placeholder' => 'Date died', 'grid' => 'col'])
  </div>

  @submit(['label' => 'Add pianist', 'block' => true])
</form>
@endslot
@endcomponent