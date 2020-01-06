<tr>
  @include('components.datatable.checkbox')

  @include('components.datatable.date', ['date' => $item->created_at])
 
  <td class="dataTables_main_column" title="Subscribed at {{$item->created_at->format('g:i:s a')}}">{{$item->email}}</td>
 
  <td title="{{$item->origin_url}}" style="max-width: 280px" class="text-truncate">{{$item->origin_url}}</td>

  <td>
    @toggle(['toggle' => $item->getStatusFor('newsletter_list', $boolean = true), 
             'route' => route('subscriptions.toggle-status', ['subscription' => $item->email, 'list' => 'newsletter_list'])])
  </td>

  <td>
    @toggle(['toggle' => $item->getStatusFor('birthday_list', $boolean = true), 
             'route' => route('subscriptions.toggle-status', ['subscription' => $item->email, 'list' => 'birthday_list'])])
  </td>

  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => "mailto:$item->email", 'title' => 'Contact subscriber', 'icon' => 'envelope']],
      'delete' => route('subscriptions.destroy', $item->email)
  ]])
</tr>