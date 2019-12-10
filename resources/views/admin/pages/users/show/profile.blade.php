<div class="tab-pane fade {{request('section') == 'profile' || ! request()->has('section') ? 'show active' : null}} m-3" id="profile">
  <div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
      <div class="text-center rounded bg-light px-3 py-2">
        <p class="text-muted mb-2 pb-2 border-bottom"><strong>My age range is...</strong></p>
        <p class="m-0">{{$user->age_range ?? 'Unknown'}}</p>          
      </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
      <div class="text-center rounded bg-light px-3 py-2">
        <p class="text-muted mb-2 pb-2 border-bottom"><strong>I consider my piano experience to be...</strong></p>
        <p class="m-0">{{$user->experience ? ucfirst($user->experience) : 'Unknown'}} {{$user->experience ? "($user->preferredLevel)" : null}}</p>          
      </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
      <div class="text-center rounded bg-light px-3 py-2">
        <p class="text-muted mb-2 pb-2 border-bottom"><strong>The piece I like the most is...</strong></p>
        <p class="m-0 clamp-1">{{$user->preferred_piece ? $user->preferred_piece->medium_name : 'Unknown'}}</p>          
      </div>        
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
      <div class="text-center rounded bg-light px-3 py-2">
        <p class="text-muted mb-2 pb-2 border-bottom"><strong>I came to PianoLit because I'm a...</strong></p>
        <p class="m-0">{{$user->occupation ? ucfirst($user->occupation) : 'Unknown'}}</p>          
      </div>            
    </div>
  </div>
</div>