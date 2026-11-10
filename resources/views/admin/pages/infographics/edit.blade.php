@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Edit '.$infograph->name,
        'subtitle' => 'Manage infographic details, image and topics.',
        'breadcrumbs' => [['label' => 'Infographics', 'url' => route('admin.infographs.index', $returnFilters)]],
    ])
    @include('admin.pages.infographics.form')
</div></div>
@endsection
