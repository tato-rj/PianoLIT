<div class="form-group">
    <input required type="text" name="first_name" placeholder="First name" class="form-control w-100 input-light" value="{{ old('first_name') }}">
    @include('components/form/error', ['field' => 'first_name'])
</div>
<div class="form-group">
    <input required type="text" name="last_name" placeholder="Last name" class="form-control w-100 input-light" value="{{ old('last_name') }}">
    @include('components/form/error', ['field' => 'last_name'])
</div>
<div class="form-group">
    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
    @include('components/form/error', ['field' => 'email'])
</div>
<div class="form-group">
    <input required type="password" name="password" placeholder="Password" class="form-control w-100 input-light" value="{{ old('password') }}">
    @include('components/form/error', ['field' => 'password'])
</div>
<div class="form-group">
    <input required type="password" name="password_confirmation" placeholder="Confirm your password" class="form-control w-100 input-light" value="{{ old('password') }}">
    @include('components/form/error', ['field' => 'password'])
</div>

<input type="hidden" name="origin" value="web">