@component('layouts.overlay', ['name' => 'crashcourse', 'light' => false, 'position' => 'center', 'background' => '0,0,0,0.8'])
<div class="cc-container">
  @include('crashcourses.card', [
  	'crashcourse' => \App\CrashCourse\Crashcourse::published()->first(),
  	'phoneXpos' => '-14px',
  	'hidePhoneOnMobile' => true,
  	'hidePhoneOnOverflow' => false,
  ])
</div>
@endcomponent