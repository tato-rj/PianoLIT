<div class="form-group type-container {{$type ?? ''}} mb-2 videos-form" style="display: {{$display ?? null}}">
	<div class="d-flex d-apart mb-2 quick-fill">
		<div class="btn btn-sm btn-outline-secondary cursor-pointer default-performance" style="display: none;" data-title="Performance" data-description="Watch a video recording of this piece">Default performance</div>
		<div>
			<select class="form-control form-control-sm" data-name="tutorial-description">
				<option selected disabled>Common tutorials</option>
				<optgroup label="Play along">
					<option data-title="Slow performance" value="Practice along with a slow video recording of this piece">Slow performance</option>					
				</optgroup>
				<optgroup label="Technique">
					<option data-title="Tutorial" value='How to avoid unwanted accents when playing longer melodic lines'>Avoiding accents</option>
					<option data-title="Tutorial" value='Exercises to increase dexterity and play fast scales with a clean and even sound'>Fast scales</option>
					<option data-title="Tutorial" value='How to play fast staccato notes, strengthening hands and fingers'>Staccato and finger strength</option>
					<option data-title="Tutorial" value='Using your arm and the concept of "free-fall" to create a consistent solid sound'>Free-fall</option>
					<option data-title="Tutorial" value='Learn how to play full scales with even and clean sound'>Learning scales</option>
					<option data-title="Tutorial" value='Using gestures to improve rhythm and give a natural shape to musica lines'>Musical gestures</option>
					<option data-title="Tutorial" value='Improve the accuracy of your jumbs with the "stop and prepare" technique'>Stop and prepare</option>
				</optgroup>
				<optgroup label="Sound">
					<option data-title="Tutorial" value='Softening the edges of your phrases and creating that "arc-shape" sound'>Arc-shape sound</option>
					<option data-title="Tutorial" value="Vertical lines: what they are and why we need to avoid them">Avoiding vertical lines</option>
					<option data-title="Tutorial" value="How to group notes into phrases and shape your lines with dynamic control">Dynamics and phrasing</option>
					<option data-title="Tutorial" value='How to balance the sound between your hands'>Hands balance</option>
					<option data-title="Tutorial" value="Playing with clarity without over-holding notes">Over-holding</option>
					<option data-title="Tutorial" value="The importance of controlling the sound of your thumb">Thumb control</option>
					<option data-title="Tutorial" value="How to project the top note of chords and why this matters">Voicing chords</option>
				</optgroup>
				<optgroup label="Theory">
					<option data-title="Tutorial" value="What is a minuet? Let's learn about the form and why it matters">Minuets 101</option>
					<option data-title="Tutorial" value="The basics of sonata-form you need to know and why it matters">Sonata-form</option>
					<option data-title="Tutorial" value="Explore the harmony to find out how to shape musical lines">Expressive harmony</option>
				</optgroup>
			</select>
		</div>
	</div>

	<input rows="1" class="form-control-sm form-control mb-1 video-title" placeholder="Title" name="{{$names[0] ?? null}}" value="{{$title ?? null}}">
	<input rows="1" class="form-control-sm form-control mb-1 video-description" placeholder="Description" name="{{$names[1] ?? null}}" value="{{$description ?? null}}">
	
	@if(! empty($type) && $type == 'original-type')
	<input rows="1" class="form-control form-control-sm mb-1 videos-link" placeholder="File name">
	@else
	<div class="input-group input-group-sm mb-1">
		<div class="input-group-prepend">
			<a href="{{$url}}" target="_blank" class="input-group-text no-underline"><i class="text-success fas fa-globe"></i></a>
		</div>
		<input rows="1" class="form-control videos-link" placeholder="File name" name="{{$names[2]}}" value="{{$filename}}">		
	</div>
	@endif

	<a class="align-self-stretch btn btn-sm btn-block btn-danger text-white mr-1 remove-field mb-4">
		<strong>Remove</strong>
	</a>

</div>