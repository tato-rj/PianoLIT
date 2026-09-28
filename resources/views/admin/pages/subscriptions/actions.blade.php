@include('components.datatable.actions', ['actions' => [
    'other' => [['route' => "mailto:$item->email", 'title' => 'Contact subscriber', 'icon' => 'mail']],
    'delete' => route('admin.subscriptions.destroy', $item->email)
]])