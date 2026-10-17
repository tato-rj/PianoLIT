<div class="text-center py-4">
    <p>{{ $message ?? 'Create your free account to save favorites and get personal suggestions.' }}</p>
    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill me-2">Sign in</a>
    <a href="{{ route('register') }}" class="btn btn-outline-secondary rounded-pill">Sign up free</a>
</div>
