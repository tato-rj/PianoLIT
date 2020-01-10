<div>
  @toggle(['toggle' => $item->getStatusFor('birthday_list', $boolean = true), 
  'route' => route('subscriptions.toggle-status', ['subscription' => $item->email, 'list' => 'birthday_list'])])
</div>