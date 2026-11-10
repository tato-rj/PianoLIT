@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'New infographic',
        'subtitle' => 'Add details, upload an image and select topics.',
        'breadcrumbs' => [['label' => 'Infographics', 'url' => route('admin.infographs.index', $returnFilters)]],
    ])
    @include('admin.pages.infographics.form', ['infograph' => null])
</div></div>
@endsection
