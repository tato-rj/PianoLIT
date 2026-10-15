@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid user-profile" data-user-profile>
    @component('admin.components.page.title', ['title' => 'User details', 'subtitle' => 'Profile, membership and saved music.'])
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">@icon('arrow-left')All users</a>
        @unless($analyticsExcluded)<a class="btn btn-dark" href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits']) }}">@icon('chart-line')Logs & analytics</a>@endunless
        <a class="btn btn-secondary" href="{{ route('impersonate', $user) }}" target="_blank" rel="noopener noreferrer">@icon('contact-round')Impersonate</a>
    @endcomponent

    @include('admin.pages.users.show.profile')
    <div class="up-grid">
        @include('admin.pages.users.show.membership')
        <section class="up-card" aria-labelledby="up-account-title">
            <div class="up-section-title"><span class="up-icon">@icon('user-round', ['mr' => 0])</span><div><h2 id="up-account-title">Account details</h2><p>Registration and profile information.</p></div></div>
            <dl class="up-facts">
                @include('admin.pages.users.show.fact', ['label' => 'User ID', 'value' => '#'.$user->id])
                @include('admin.pages.users.show.fact', ['label' => 'Email', 'value' => $user->email])
                @include('admin.pages.users.show.fact', ['label' => 'Email confirmed', 'value' => $user->email_verified_at ? $formatDate($user->email_verified_at) : 'Not confirmed'])
                @include('admin.pages.users.show.fact', ['label' => 'Joined', 'value' => $formatDate($user->created_at)])
                @include('admin.pages.users.show.fact', ['label' => 'Profile updated', 'value' => $formatDate($user->updated_at)])
                @include('admin.pages.users.show.fact', ['label' => 'Signup platform', 'value' => $user->origin === 'ios' ? 'iOS' : ucfirst($user->origin ?: 'Not recorded')])
                @include('admin.pages.users.show.fact', ['label' => 'Language / locale', 'value' => str_replace('_', ' · ', $user->locale ?: '')])
                @include('admin.pages.users.show.fact', ['label' => 'Occupation', 'value' => ucfirst($user->occupation ?: '')])
                @include('admin.pages.users.show.fact', ['label' => 'Piano experience', 'value' => ucfirst($user->experience ?: '')])
                @include('admin.pages.users.show.fact', ['label' => 'Age range', 'value' => $user->age_range])
                @include('admin.pages.users.show.fact', ['label' => 'Gender', 'value' => ucfirst($user->gender ?: '')])
            </dl>
            <p class="up-note">Dates shown in {{ config('app.timezone') }}.</p>
        </section>
        <section class="up-card" aria-labelledby="up-signin-title">
            <div class="up-section-title"><span class="up-icon up-icon--lavender">@icon('lock', ['mr' => 0])</span><div><h2 id="up-signin-title">Sign-in & email</h2><p>Connected sign-in methods and mailing preferences.</p></div></div>
            <div class="up-methods">
                @forelse($signInMethods as $method)
                <div class="up-method"><span class="up-method-check">@icon('circle-check', ['mr' => 0])</span><div><strong>{{ $method['label'] }}</strong><span>{{ $method['email'] ?: 'Provider email not shared' }}</span>@if($method['linked_at'])<small>Linked {{ $formatDate($method['linked_at']) }}</small>@endif</div></div>
                @empty<p class="up-muted">No sign-in method recorded.</p>@endforelse
            </div>
            <div class="up-divider"></div>
            <h3>Mailing lists</h3>
            @if($newsletter && $newsletter->lists->isNotEmpty())
                <div class="up-pills">@foreach($newsletter->lists as $list)<span class="up-pill">{{ $list->name }}</span>@endforeach</div>
                <p class="up-note">Subscribed {{ $formatDate($newsletter->created_at) }}.</p>
            @else<p class="up-muted">Not subscribed to a mailing list.</p>@endif
        </section>
        <section class="up-card" aria-labelledby="up-location-title">
            <div class="up-section-title"><span class="up-icon up-icon--peach">@icon('map-pin', ['mr' => 0])</span><div><h2 id="up-location-title">Saved location</h2><p>The location saved on this account.</p></div></div>
            @if($user->location)
            <dl class="up-facts">
                @include('admin.pages.users.show.fact', ['label' => 'Country', 'value' => $user->location->countryName])
                @include('admin.pages.users.show.fact', ['label' => 'Region', 'value' => $user->location->regionName])
                @include('admin.pages.users.show.fact', ['label' => 'City', 'value' => $user->location->cityName])
                @include('admin.pages.users.show.fact', ['label' => 'Saved IP', 'value' => $user->location->ip])
                @include('admin.pages.users.show.fact', ['label' => 'Coordinates', 'value' => $user->location->latitude !== null && $user->location->longitude !== null ? $user->location->latitude.', '.$user->location->longitude : null])
            </dl>
            <p class="up-note">An IP-derived location can be approximate; it is not a live location.</p>
            @else<div class="up-empty">@icon('map-pin', ['mr' => 0])<p>No location saved.</p></div>@endif
        </section>
    </div>
    @include('admin.pages.users.show.library')
    @include('admin.pages.users.show.manage')
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-user-profile.js') }}"></script>
@endsection
