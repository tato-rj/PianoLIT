<div class="text-center py-4">
    <p>{{ $message ?? 'Sign in to save your favorites and get personalized suggestions.' }}</p>
    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill mr-2">Sign in</a>
    <a href="{{ route('register') }}" class="btn btn-outline-secondary rounded-pill">Create an account</a>
</div>
