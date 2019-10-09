<div class="btn-group btn-group-toggle" data-toggle="buttons">
<label class="btn btn-light cursor-pointer {{$gender == 'male' ? 'active' : null}}">
  <input type="radio" title="Male" name="gender" autocomplete="off" value="male" {{$gender == 'male' ? 'checked' : null}}><i class="fas fa-male"></i>
</label>
<label class="btn btn-light cursor-pointer {{$gender == 'female' ? 'active' : null}}">
  <input type="radio" title="Female" name="gender" autocomplete="off" value="female" {{$gender == 'female' ? 'checked' : null}}><i class="fas fa-female"></i>
</label>
</div>