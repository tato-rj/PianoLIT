@if(!$recentProof)
    @if($hasPassword)
        @input(['id' => $proofId, 'label' => 'Current password', 'name' => 'current_password', 'bag' => $bag, 'type' => 'password', 'autocomplete' => 'current-password', 'limit' => 1024])
    @else
        <p class="small text-muted">We’ll ask you to confirm your identity before making this change.</p>
    @endif
@endif
