@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Edit infographic', 'subtitle' => $infograph->name,
        'breadcrumbs' => [['label' => 'Infographics', 'url' => route('admin.infographs.index', $returnFilters)]],
    ])
    <div class="row g-4 mb-4 align-items-start">
        <div class="col-lg-8">
            @component('admin.components.editor.section', ['id' => 'infographic-details', 'title' => 'Infographic details'])
                <form method="POST" action="{{ route('admin.infographs.update', ['infograph' => $infograph] + $returnFilters) }}" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    @include('admin.pages.infographics.fields')
                    <div class="text-end"><button type="submit" class="btn btn-primary btn-sm">@icon('check')Save changes</button></div>
                </form>
            @endcomponent
        </div>
        <div class="col-lg-4">
            @component('admin.components.editor.section', ['id' => 'infographic-preview', 'title' => 'Current image'])
                @if($infograph->cover_path)
                <a href="{{ storage($infograph->cover_path) }}" target="_blank" rel="noopener noreferrer" aria-label="Open current infographic image"><img src="{{ storage($infograph->cover_path) }}" alt="{{ $infograph->name }}" class="w-100 rounded"></a>
                @else
                <p class="text-muted">No image uploaded.</p>
                @endif
                <dl class="row small mt-3 mb-0">
                    <dt class="col-6">Dimensions</dt><dd class="col-6 text-end">{{ number_format($infograph->width) }} × {{ number_format($infograph->height) }} px</dd>
                    <dt class="col-6">Downloads</dt><dd class="col-6 text-end">{{ number_format($infograph->downloads) }}</dd>
                    <dt class="col-6">Score</dt><dd class="col-6 text-end mb-0">{{ number_format($infograph->score) }}</dd>
                </dl>
            @endcomponent
        </div>
    </div>
</div></div>
@endsection
