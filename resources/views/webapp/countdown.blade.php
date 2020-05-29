@component('auth.webapp.layout', ['title' => 'Coming up soon', 'subtitle' => 'We will be launching our WebApp on <strong>Monday June 1st</strong>, stay tuned!'])
<h6>Going live in...</h6>
<div id="countdown" class="d-flex">
	<div class="text-center bg-light rounded mr-2 p-3">
		<h5 class="d-flex flex-center mx-auto" style="width: 44px; height: 44px; background-color: rgba(0,0,0,0.06); border-radius: 50%" id="days"></h5>
		<div class="text-muted"><small><strong>days</strong></small></div>
	</div>
	<div class="text-center bg-light rounded mr-2 p-3">
		<h5 class="d-flex flex-center mx-auto" style="width: 44px; height: 44px; background-color: rgba(0,0,0,0.06); border-radius: 50%" id="hours"></h5>
		<div class="text-muted"><small><strong>hours</strong></small></div>
	</div>
	<div class="text-center bg-light rounded mr-2 p-3">
		<h5 class="d-flex flex-center mx-auto" style="width: 44px; height: 44px; background-color: rgba(0,0,0,0.06); border-radius: 50%" id="minutes"></h5>
		<div class="text-muted"><small><strong>minutes</strong></small></div>
	</div>
	<div class="text-center bg-light rounded mr-2 p-3">
		<h5 class="d-flex flex-center mx-auto" style="width: 44px; height: 44px; background-color: rgba(0,0,0,0.06); border-radius: 50%" id="seconds"></h5>
		<div class="text-muted"><small><strong>seconds</strong></small></div>
	</div>
</div>

<script>
// Set the date we're counting down to
var countDownDate = new Date("Jun 1, 2020 12:00:00").getTime();

// Update the count down every 1 second
var x = setInterval(function() {

  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDate - now;

  // Time calculations for days, hours, minutes and seconds
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="countdown"
  document.getElementById("days").innerHTML = days;
  document.getElementById("hours").innerHTML = hours;
  document.getElementById("minutes").innerHTML = minutes;
  document.getElementById("seconds").innerHTML = seconds;

  // If the count down is finished, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("countdown").innerHTML = "EXPIRED";
  }
}, 1000);
</script>
@endcomponent
