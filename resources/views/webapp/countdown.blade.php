@component('auth.webapp.layout', ['title' => 'Coming up soon', 'subtitle' => 'We will be launching our WebApp soon, stay tuned!'])

<div class="text-center mb-3">
	<div><small>save the date</small></div>
	<h5>Monday June 1st</h5>
</div>

<div id="countdown" class="d-flex flex-wrap justify-content-center">
	<div class="text-center mr-2 mb-2" style="width: 80px">
		<h4 class="d-flex flex-center mx-auto py-3 bg-light rounded-top m-0 border-top border-left border-right" style="height: 60px" id="days"></h4>
		<div class="border bg-white py-2 rounded-bottom"><small><strong>days</strong></small></div>
	</div>
	<div class="text-center mr-2 mb-2" style="width: 80px">
		<h4 class="d-flex flex-center mx-auto py-3 bg-light rounded-top m-0 border-top border-left border-right" style="height: 60px" id="hours"></h4>
		<div class="border bg-white py-2 rounded-bottom"><small><strong>hours</strong></small></div>
	</div>
	<div class="text-center mr-2 mb-2" style="width: 80px">
		<h4 class="d-flex flex-center mx-auto py-3 bg-light rounded-top m-0 border-top border-left border-right" style="height: 60px" id="minutes"></h4>
		<div class="border bg-white py-2 rounded-bottom"><small><strong>minutes</strong></small></div>
	</div>
	<div class="text-center mr-2 mb-2" style="width: 80px">
		<h4 class="d-flex flex-center mx-auto py-3 bg-light rounded-top m-0 border-top border-left border-right" style="height: 60px" id="seconds"></h4>
		<div class="border bg-white py-2 rounded-bottom"><small><strong>seconds</strong></small></div>
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
