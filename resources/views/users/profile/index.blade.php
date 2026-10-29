@extends('layouts.app', ['title' => 'My profile'])

@section('content')
<div class="container profile-page py-4 py-md-5">
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-4 mb-md-5">
        <div>
            <h1 class="mb-2">My profile</h1>
            <p class="text-muted mb-0">Manage your profile, security and email preferences.</p>
        </div>
        <nav class="d-flex flex-wrap gap-3" aria-label="Your purchases">
            <a class="btn btn-secondary btn-sm" href="{{ route('users.orders') }}">@icon('package')Track your orders</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('users.purchases') }}">@icon('download')My downloads</a>
        </nav>
    </header>
    <div class="row g-4 g-lg-5">
        <aside class="col-lg-3">
            @include('users.profile.navigation')
        </aside>
        <div class="col-lg-9 profile-content">
            <section id="profile" class="profile-section" aria-labelledby="profile-heading">
                <h2 id="profile-heading">Profile</h2>
                <p class="text-muted">The basics of your account, all in one place.</p>
                <div class="profile-panel mb-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div><h3 class="h5 mb-1 text-break">{{ trim($user->full_name) ?: 'Your account' }}</h3><p class="text-muted mb-0 text-break">{{ $user->email }}</p></div>
                    </div>
                    <dl class="row mb-0 profile-facts">
                        <div class="col-sm-6 mb-3"><dt>Member since</dt><dd class="mb-0">{{ $user->created_at ? $user->created_at->format('F j, Y') : 'Not recorded' }}</dd></div>
                        <div class="col-sm-6 mb-3"><dt>Signed up through</dt><dd class="mb-0">{{ ['web' => 'PianoLIT website', 'webapp' => 'PianoLIT web app', 'ios' => 'PianoLIT iOS app', 'android' => 'PianoLIT Android app'][$user->origin] ?? 'Not recorded' }}</dd></div>
                        <div class="col-sm-6"><dt>Location</dt><dd class="mb-0">{{ $user->location ? (collect([$user->location->cityName, $user->location->countryName])->filter()->implode(', ') ?: 'Not recorded') : 'Not recorded' }}</dd></div>
                        <div class="col-sm-6 mt-3 mt-sm-0"><dt>Email status</dt><dd class="mb-0">{{ $user->email_verified_at ? 'Verified' : 'Not yet verified' }}</dd></div>
                    </dl>
                    <p class="small text-muted mt-3 mb-0">Location is approximate and may reflect a later visit.</p>
                </div>
                <form class="profile-panel" method="POST" action="{{ route('users.profile.details') }}" disable-on-submit>
                    @csrf @method('PATCH')
                    <h3 class="h5 mb-3">Personal information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">@input(['id' => 'profile-first-name', 'label' => 'First name', 'name' => 'first_name', 'bag' => 'profileDetails', 'value' => $user->first_name, 'limit' => 200, 'autocomplete' => 'given-name'])</div>
                        <div class="col-md-6">@input(['id' => 'profile-last-name', 'label' => 'Last name', 'name' => 'last_name', 'bag' => 'profileDetails', 'value' => $user->last_name, 'limit' => 200, 'autocomplete' => 'family-name', 'required' => false])</div>
                    </div>
                    <div class="d-flex justify-content-end"><button class="btn btn-primary" type="submit">Save changes</button></div>
                </form>
            </section>
            <section id="security" class="profile-section" aria-labelledby="security-heading">
                <h2 id="security-heading">Security & linked accounts</h2>
                <p class="text-muted">Choose how you sign in and keep your account details up to date.</p>
                <form class="profile-panel mb-4" method="POST" action="{{ route('users.profile.email') }}" disable-on-submit>
                    @csrf @method('PATCH')
                    <h3 class="h5">Email address</h3>
                    <p class="text-muted text-break">Currently {{ $user->email }}</p>
                    @input(['id' => 'profile-email', 'label' => 'New email address', 'name' => 'email', 'bag' => 'emailChange', 'value' => $user->email, 'type' => 'email', 'limit' => 255, 'autocomplete' => 'email', 'describedBy' => 'profile-email-help'])
                    <p id="profile-email-help" class="small text-muted">We’ll send a confirmation link to your new address before making the change.</p>
                    @include('users.profile.proof', ['bag' => 'emailChange', 'proofId' => 'email-current-password'])
                    <div class="d-flex justify-content-end"><button class="btn btn-primary" type="submit">Update email</button></div>
                </form>
                <form class="profile-panel mb-4" method="POST" action="{{ route('users.profile.password') }}" disable-on-submit>
                    @csrf @method('PATCH')
                    <h3 class="h5">{{ $hasPassword ? 'Password' : 'Set a password' }}</h3>
                    <p class="text-muted">{{ $hasPassword ? 'Use a strong password you don’t use anywhere else.' : 'Add an email and password sign-in alongside your linked accounts.' }}</p>
                    @include('users.profile.proof', ['bag' => 'passwordChange', 'proofId' => 'password-current-password'])
                    <div class="row g-3">
                        <div class="col-md-6">@input(['id' => 'profile-password', 'label' => 'New password', 'name' => 'password', 'bag' => 'passwordChange', 'type' => 'password', 'autocomplete' => 'new-password', 'limit' => 1024, 'describedBy' => 'profile-password-help'])</div>
                        <div class="col-md-6">@input(['id' => 'profile-password-confirmation', 'label' => 'Confirm new password', 'name' => 'password_confirmation', 'bag' => 'passwordChange', 'type' => 'password', 'autocomplete' => 'new-password', 'limit' => 1024])</div>
                    </div>
                    <p id="profile-password-help" class="small text-muted">At least 8 characters.</p>
                    <div class="d-flex justify-content-end"><button class="btn btn-primary" type="submit">{{ $hasPassword ? 'Update password' : 'Set password' }}</button></div>
                </form>
                <div class="profile-panel">
                    <h3 class="h5 mb-1">Linked accounts</h3>
                    <p class="text-muted mb-0">Use Google or Apple to sign in to this same PianoLIT account.</p>
                    @foreach($links as $provider => $connection)
                        @include('users.profile.linked-account')
                    @endforeach
                </div>
            </section>
            <section id="email-preferences" class="profile-section" aria-labelledby="email-heading" data-profile-emails>
                <h2 id="email-heading">Email preferences</h2>
                <p class="text-muted">More of what you love. Choose the emails you’d like to receive.</p>
                <div class="profile-panel">
                    <p class="small text-muted mb-0 text-break">Sent to {{ $user->email }} <span data-profile-autosave hidden>· Changes save automatically</span></p>
                    @forelse($lists as $list)
                        <form class="profile-list-row" method="POST" action="{{ route('users.profile.subscription', $list) }}" data-profile-list>
                            @csrf @method('PATCH')
                            <div class="d-flex align-items-start justify-content-between gap-4">
                                <div><h3 class="h6 mb-2">{{ $list->name }}</h3><p class="text-muted mb-0" id="email-list-description-{{ $list->id }}">{{ $list->description }}</p></div>
                                <div class="pt-1 flex-shrink-0">
                                    <input type="hidden" name="enabled" value="0">
                                    @toggle(['toggle' => $subscribedListIds->contains($list->id), 'name' => 'enabled', 'inputValue' => '1', 'ariaLabel' => 'Subscribe to '.$list->name, 'describedBy' => 'email-list-description-'.$list->id])
                                </div>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm mt-3" data-profile-list-save>Save preference</button>
                        </form>
                    @empty
                        <p class="mt-4 mb-0">There are no email lists available yet. Check back soon.</p>
                    @endforelse
                    <p class="small mb-0" role="status" aria-live="polite" data-email-status></p>
                    @if($lists->count() > 1)
                        <form class="pt-3 border-top" method="POST" action="{{ route('users.profile.unsubscribe-all') }}" disable-on-submit>
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-secondary" data-unsubscribe-all @if($subscribedListIds->isEmpty()) disabled @endif>Unsubscribe from all</button>
                        </form>
                    @endif
                    <p class="small text-muted mt-3 mb-0">Essential account and purchase emails are sent separately from these lists.</p>
                </div>
            </section>
            <section id="delete-account" class="profile-section" aria-labelledby="delete-heading">
                <h2 id="delete-heading">Delete account</h2>
                <p class="text-muted">Permanently close your PianoLIT account.</p>
                <div class="profile-panel">
                    <p class="mb-2">Your profile and saved content will be permanently removed, and all email subscriptions will stop.</p>
                    <p class="text-muted mb-0">Some purchase and usage records are kept internally for accounting and analytics. New accounts won’t recover previous purchases or activity.</p>
                    @if($removalBlocked)
                        <div class="border-top mt-4 pt-4">
                            <h3 class="h6 mb-1">Your membership needs to end first</h3>
                            <p class="text-muted mb-3">Deleting your account won’t stop billing.</p>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <a class="btn btn-secondary" href="{{ route('webapp.membership.edit') }}">Manage membership</a>
                                <a href="{{ route('contact') }}">Need help?</a>
                            </div>
                        </div>
                    @else
                        <details class="profile-delete-confirm mt-4" @if($errors->getBag('deleteAccount')->any()) open @endif>
                            <summary class="text-danger">Delete my account</summary>
                            <form class="mt-4" method="POST" action="{{ route('users.profile.destroy') }}" disable-on-submit>
                                @csrf @method('DELETE')
                                @include('components.forms.error', ['bag' => 'deleteAccount', 'field' => 'account_removal'])
                                @input(['id' => 'delete-confirmation-email', 'label' => 'Type your email address to confirm', 'name' => 'confirmation_email', 'bag' => 'deleteAccount', 'type' => 'email', 'autocomplete' => 'off', 'limit' => 255, 'placeholder' => $user->email])
                                @include('users.profile.proof', ['bag' => 'deleteAccount', 'proofId' => 'delete-current-password'])
                                <button type="submit" class="btn btn-danger">Permanently delete account</button>
                            </form>
                        </details>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/profile.js') }}" defer></script>
@endpush
