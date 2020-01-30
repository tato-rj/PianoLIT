<tr>

  <td>{{carbon($item->sent_at)->toFormattedDateString()}}</td>
  
  <td class="text-truncate">{{$item->name}}</td>
  
  <td>{{$item->emails_count}}</td>

  <td>{{$item->opens_count}}</td>

  <td>{{$item->clicks_count}}</td>

  <td>
    @include('components.datatable.actions', ['actions' => [
        'other' => [
          ['route' => route('admin.subscriptions.reports.show', $item->list_id), 'title' => "More details", 'icon' => 'eye', 'target' => null]
        ]
    ]])
  </td>
</tr>