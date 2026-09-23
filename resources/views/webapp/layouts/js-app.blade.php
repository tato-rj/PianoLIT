@php
    $browserApp = [
        'csrfToken' => csrf_token(),
        'url' => request()->root(),
        'user' => auth('web')->check() ? auth('web')->user()->only(['id', 'first_name', 'last_name', 'full_name', 'email']) : null,
    ];
@endphp
<script>
    window.app = @json($browserApp);
</script>
