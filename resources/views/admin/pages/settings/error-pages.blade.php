<section class="design-system__section" id="ds-error-pages" aria-labelledby="ds-error-pages-title">
    <header class="design-system__section-heading">
        <h2 id="ds-error-pages-title">Error pages</h2>
        <p>One shared page across all hubs, with the admin logo only on the admin host. These previews render the actual templates. Their links are disabled in this gallery.</p>
    </header>
    <div class="design-system__grid">
        @foreach([['status' => 404, 'admin' => false], ['status' => 500, 'admin' => true], ['status' => 413, 'admin' => false]] as $example)
            @php
                $errorRequest = \Illuminate\Http\Request::create('https://'.($example['admin'] ? 'admin.' : 'learn.').config('app.short_url').'/example', $example['status'] === 413 ? 'POST' : 'GET');
                $preview = view('errors.page', ['status' => $example['status'], 'errorRequest' => $errorRequest])->render();
            @endphp
            <article class="design-system__sample">
                <div class="design-system__sample-label">{{ $example['status'] }} · {{ $example['admin'] ? 'Admin' : 'Public hub' }}</div>
                <iframe title="{{ $example['status'] }} error page preview" srcdoc="{{ $preview }}" sandbox inert loading="lazy" width="100%" height="780" style="border: 0;"></iframe>
            </article>
        @endforeach
    </div>
</section>
