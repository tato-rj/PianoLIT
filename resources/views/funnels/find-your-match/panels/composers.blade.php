@component('funnels.find-your-match.panels.panel', ['loop' => $loop ?? false, 'question' => 'Pick your top 3 favorite composers'])

@include('funnels.find-your-match.components.images', [
	'images' => [
		\App\Composer::where('name', 'Johann Sebastian Bach')->first()->cover_image => ['Johann Sebastian Bach', 'counterpoint'],
		\App\Composer::where('name', 'Ludwig Van Beethoven')->first()->cover_image => ['Ludwig Van Beethoven', 'bold'],
		\App\Composer::where('name', 'Frédéric Chopin')->first()->cover_image => ['Frédéric Chopin', 'melancholic'],
		\App\Composer::where('name', 'Florence Price')->first()->cover_image => ['Florence Price', 'dreamy'],
		\App\Composer::where('name', 'Thomas Wiggins')->first()->cover_image => ['Thomas Wiggins', 'passionate'],
		\App\Composer::where('name', 'Cécile Chaminade')->first()->cover_image => ['Cécile Chaminade', 'melancholic'],
	]
])

@endcomponent