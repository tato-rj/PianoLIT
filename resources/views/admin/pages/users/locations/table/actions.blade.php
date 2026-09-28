<div class="text-right">
@button([
	'label' => \App\Support\Icon::render('map-pin', ['mr' => 2]) . 'Find on map',
	'styles' => [
		'size' => 'sm', 
		'theme' => 'grey'
		], 
	'classes' => 'rounded', 
	'external' => true,
	'href' => $item->googlemap])
</div>