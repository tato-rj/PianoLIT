@include('components.datatable.actions', ['actions' => [
    'other' => [['route' => "mailto:$item->email", 'title' => 'Contact subscriber', 'icon' => 'envelope']],
    'delete' => route('admin.subscriptions.destroy', $item->email)
]])