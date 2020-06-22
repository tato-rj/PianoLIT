@extends('layouts.app', [
  'title' => 'Infographics | ' . config('app.name'),
  'noclicks' => true,
  'shareable' => [
    'keywords' => 'infographic,infograph,learn music,music theory,music sheet,piano sheet,treble sheet,bass sheet',
    'title' => 'Infographics',
    'description' => 'Cool infographics about all music things related',
    'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
    'created_at' => carbon('28-08-2019'),
    'updated_at' => carbon('28-08-2019')
  ]])

@section('content')
<section class="container mb-6">
	<div class="row">
    <div class="col-12 border-bottom mb-4 pb-4">
      <p class="text-muted mb-1"><small>INFOGRAPHICS ABOUT</small></p>
      <h2>{{ucfirst($topic->name)}}</h2>
    </div>
		<div class="col-lg-3 col-md-3 col-12">
      <p><strong>Other topics</strong></p>
      <div class="d-flex flex-wrap">
        @topics(['topics' => $topics, 'route' => 'resources.infographs.topic'])
      </div>
    </div>
    <div class="col-lg-9 col-md-9 col-12">
      <div class="row mb-4"> 
        @foreach($infographs as $infograph)
        @include('infographics.card', ['sizes' => 'col-lg-4 col-6'])
        @endforeach
      </div>

      @pagination(['collection' => $infographs])
    </div>

	</div>
</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>
@endsection
