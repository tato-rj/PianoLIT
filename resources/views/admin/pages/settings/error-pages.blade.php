@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid">
        @include('admin.components.page.title', [
            'title' => 'Error pages',
            'subtitle' => 'Preview the error pages used across PianoLIT. Links inside the previews are disabled.',
        ])

        <div class="row g-4" id="error-page-previews">
        @foreach([['status' => 404, 'admin' => false], ['status' => 500, 'admin' => true], ['status' => 413, 'admin' => false]] as $example)
            @php
                $errorRequest = \Illuminate\Http\Request::create('https://'.($example['admin'] ? 'admin.' : 'learn.').config('app.short_url').'/example', $example['status'] === 413 ? 'POST' : 'GET');
                $preview = view('errors.page', ['status' => $example['status'], 'errorRequest' => $errorRequest])->render();
            @endphp
            <div class="col-12 col-lg-6 col-xl-4">
                <article class="card h-100 overflow-hidden" aria-labelledby="error-preview-{{ $example['status'] }}-title">
                    <div class="card-header">
                        <h2 class="h6 mb-0" id="error-preview-{{ $example['status'] }}-title">{{ $example['status'] }} · {{ $example['admin'] ? 'Admin' : 'Public hub' }}</h2>
                    </div>
                    <iframe title="{{ $example['status'] }} error page preview" srcdoc="{{ $preview }}" sandbox inert loading="lazy" width="100%" height="780" style="border: 0;"></iframe>
                </article>
            </div>
        @endforeach
        </div>
    </div>
</div>
@endsection
