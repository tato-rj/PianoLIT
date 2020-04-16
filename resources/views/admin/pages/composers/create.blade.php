@component('admin.components.modals.add', ['model' => 'composer'])
<form method="POST" action="{{route('admin.composers.store')}}" enctype="multipart/form-data">
  @csrf
  {{-- Name --}}
  <div class="d-flex form-group">
    <div class="flex-grow mr-2">
      <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
    </div>
    <div>
      @include('admin.pages.composers.gender', ['gender' => old('gender')])
    </div>
  </div>
  {{-- Life --}}
  <div class="form-group">
    <textarea class="form-control" rows="6" name="biography" placeholder="Life's summary" required>{{ old('biography') }}</textarea>
  </div>
  {{-- Curiosity --}}
  <div class="form-group">
    <textarea class="form-control" rows="3" name="curiosity" placeholder="Did you know?">{{ old('curiosity') }}</textarea>
  </div>
  <div class="form-row form-group">
    <div class="col">
      <div class="custom-file">
        <input type="file" class="custom-file-input {{$errors->has('cover') ? 'is-invalid' : ''}}" name="cover" id="customFile">
        <label class="custom-file-label truncate" for="customFile">Cover image</label>
      </div>
    </div>
    <div class="col">
      <select class="form-control {{$errors->has('period') ? 'is-invalid' : ''}}" name="period" >
        <option selected disabled>Period</option>
        @foreach(\App\Tag::periods()->get() as $period)
        <option value="{{$period->name}}" {{ old('period') == $period->name ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
        @endforeach
      </select>
      @include('admin.components.feedback', ['field' => 'period'])
    </div>
  </div>
  {{-- Nationality and period --}}
  <div class="form-row form-group">
    <div class="col">
      <select class="form-control {{$errors->has('country_id') ? 'is-invalid' : ''}}" name="country_id">
        <option selected disabled>Nationality</option>
        @foreach($countries as $country)
        <option value="{{$country->id}}" {{ old('country_id') == $country->id ? 'selected' : ''}}>{{$country->name}}</option>
        @endforeach
      </select>
      @include('admin.components.feedback', ['field' => 'nationality'])
    </div>
    <div class="col">
      <select class="form-control {{$errors->has('ethnicity') ? 'is-invalid' : ''}}" name="ethnicity" >
        <option selected disabled>Ethnicity</option>
        @foreach(ethnicities() as $ethnicity)
        <option value="{{$ethnicity}}" {{ old('ethnicity') == $ethnicity ? 'selected' : ''}}>{{ucfirst($ethnicity)}}</option>
        @endforeach
      </select>
      @include('admin.components.feedback', ['field' => 'ethnicity'])
    </div>
  </div>
  {{-- Dates --}}
  <div class="form-row form-group">
    <div class="col">
      <input class="form-control {{$errors->has('date_of_birth') ? 'is-invalid' : ''}}" id="born-in" type="text" name="date_of_birth" placeholder="Date born" value="{{ old('date_of_birth') }}">
    </div>
    <div class="col">
      <input class="form-control {{$errors->has('date_of_death') ? 'is-invalid' : ''}}" id="died-in" type="text" name="date_of_death" placeholder="Date died" value="{{ old('date_of_death') }}">
    </div>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" name="is_famous" id="famous-check" {{old('is_famous') ? 'checked' : null}}>
    <label class="custom-control-label" for="famous-check">Is this composer famous?</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" name="is_pedagogical" id="pedagogical-check" {{old('is_pedagogical') ? 'checked' : null}}>
    <label class="custom-control-label" for="pedagogical-check">Is this mostly a pedagogical composer?</label>
  </div>

@endcomponent