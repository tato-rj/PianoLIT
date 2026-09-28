@include('components.datatable.actions', ['actions' => [
    'other' => [
      ['route' => "mailto:$item->email", 'title' => "Send an email to $item->first_name", 'icon' => 'mail'],
      ['route' => route('impersonate', $item), 'title' => 'Impersonate user', 'icon' => 'contact-round'],
      ['route' => route('admin.users.show', $item), 'title' => 'More details', 'icon' => 'eye', 'target' => null]
    ],
    'delete' => route('admin.users.destroy', $item)
]])
