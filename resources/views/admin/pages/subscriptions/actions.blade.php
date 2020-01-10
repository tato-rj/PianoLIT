@include('components.datatable.actions', ['actions' => [
    'other' => [['route' => "mailto:$item->email", 'title' => 'Contact subscriber', 'icon' => 'envelope']],
    'delete' => route('subscriptions.destroy', $item->email)
]])