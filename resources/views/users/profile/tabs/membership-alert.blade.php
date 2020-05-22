@if(auth()->user()->hasMembershipWith('App\Billing\Sources\Apple'))
<div class="alert alert-yellow">
  @fa(['icon' => 'exclamation-triangle'])Your Apple membership <strong>will not be canceled</strong> by deleting your account. Before deleting your account, please be sure to cancel your membership on your phone settings.
</div>
@elseif(auth()->user()->hasMembershipWith('App\Billing\Sources\Stripe'))
<div class="alert alert-yellow">
  @fa(['icon' => 'exclamation-triangle'])Your membership <strong>will not be canceled</strong> by deleting your account. Before deleting your account, please be sure to cancel your membership.
</div>
@endif