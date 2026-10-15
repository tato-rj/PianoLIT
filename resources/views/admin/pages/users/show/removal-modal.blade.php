<div class="modal fade up-removal-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true" data-removal-modal @if($errors->getBag($mode)->any()) data-open-on-load @endif>
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="{{ $modalId }}-title">{{ $action }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p><strong>{{ trim($user->full_name) }}</strong><br><span class="text-muted">{{ $user->email }} · Account #{{ $user->id }}</span></p>
            <p>{{ $purge ? 'The account and its associated visits, pages and activity records will be permanently removed.' : 'The account will be permanently removed. Stored visits, pages and activity records will be kept.' }}</p>
            <p class="small text-muted">Saved music, score markings and tutorial requests are removed in both cases. Billing records are retained. Active subscriptions block this action.</p>
            @if($errors->getBag($mode)->has('account_removal'))<p class="text-danger" role="alert">{{ $errors->getBag($mode)->first('account_removal') }}</p>@endif
            <form method="POST" action="{{ route($route, $user) }}" data-removal-form data-confirm-email="{{ mb_strtolower(trim($user->email)) }}">
                @csrf
                @method('DELETE')
                <label for="{{ $modalId }}-email" class="form-label">Type this user’s email to confirm</label>
                <input id="{{ $modalId }}-email" class="form-control" type="email" name="confirmation_email" required autocomplete="off" spellcheck="false" value="{{ $errors->getBag($mode)->any() ? old('confirmation_email') : '' }}">
                @if($errors->getBag($mode)->has('confirmation_email'))<p class="text-danger small mt-2" role="alert">{{ $errors->getBag($mode)->first('confirmation_email') }}</p>@endif
                <div class="up-confirm-actions"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger" @if($removalBlocked) disabled @endif data-removal-submit data-billing-blocked="{{ $removalBlocked ? '1' : '0' }}">{{ $action }}</button></div>
            </form>
        </div>
    </div></div>
</div>
