<div>
  @toggle(['toggle' => $list->subscribers->contains($item->id), 
  'route' => route('admin.subscriptions.lists.status', ['list' => $list->id, 'subscriberId' => $item->id]), 'autoToggle' => true])
</div>