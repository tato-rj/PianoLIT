@extends('shop.products.index', [
	'title' => 'eScores', 
	'subtitle' => 'A curated collection of unique piano pieces',
	'keywords' => $product->keywords(),
	'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
    'ads' => ['ebook']
])