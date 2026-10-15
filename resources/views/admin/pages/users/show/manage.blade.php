<section class="up-card up-removal" aria-labelledby="up-removal-title">
    <div class="up-section-title"><span class="up-icon up-icon--red">@icon('trash-2', ['mr' => 0])</span><div><h2 id="up-removal-title">Account actions</h2><p>Permanent account removal, with two choices for activity history.</p></div></div>
    @if($removalBlocked)<div class="up-billing-block" role="note">@icon('shield-user', ['mr' => 0])<div><strong>Account removal is blocked</strong><p>{{ $removalBlocked }}</p><p>Billing status is checked again when either action is submitted. <a href="#up-membership-title">Review membership</a></p></div></div>@endif
    @foreach(['deleteAccount', 'purgeAccount'] as $bag)
        @if($errors->getBag($bag)->has('account_removal'))
            <p class="up-removal-error" role="alert">{{ $errors->getBag($bag)->first('account_removal') }}</p>
        @endif
    @endforeach
    <div class="up-removal-grid">
        <article class="up-removal-option"><span class="up-pill">Keeps visits & activity</span><h3>Delete account</h3><p>Removes the account, sign-in connections, saved music, markings and tutorial requests. Stored visits, pages and activity records remain.</p><button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-user-modal" @if($removalBlocked) disabled @endif>@icon('trash-2')Delete account</button></article>
        <article class="up-removal-option up-removal-option--purge"><span class="up-pill up-pill--red">Also removes visits & activity</span><h3>Purge account</h3><p>Deletes the same account data, plus its associated visits, pages, events and old SQL activity summaries. Other accounts’ activity stays intact.</p><button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#purge-user-modal" @if($removalBlocked) disabled @endif>@icon('skull')Purge account</button></article>
    </div>
    <p class="up-note">Both actions are permanent. Billing and payment records are retained. Active subscriptions must end first; these actions do not cancel Stripe or App Store billing.</p>
</section>
@include('admin.pages.users.show.removal-modal', ['mode' => 'deleteAccount', 'modalId' => 'delete-user-modal', 'action' => 'Delete account', 'route' => 'admin.users.destroy', 'purge' => false])
@include('admin.pages.users.show.removal-modal', ['mode' => 'purgeAccount', 'modalId' => 'purge-user-modal', 'action' => 'Purge account', 'route' => 'admin.users.purge', 'purge' => true])
