<div>
  @toggle(['toggle' => $item->getStatusFor('newsletter_list', $boolean = true), 
  'route' => route('subscriptions.toggle-status', ['subscription' => $item->email, 'list' => 'newsletter_list'])])
</div>