<tr>

  <td>{{carbon($item->sent_at)->toFormattedDateString()}}</td>
  
  <td class="text-truncate">{{$item->name}}</td>
  
  <td>{{$item->emails_count}}</td>

  <td title="{{$item->delivered_count}} delivered">{{percentage($item->delivered_count, $item->emails_count)}}%</td>

  <td title="{{$item->failed_count}} failed">{{percentage($item->failed_count, $item->emails_count)}}%</td>

  <td title="{{$item->opens_count}} opened">{{percentage($item->opens_count, $item->emails_count)}}%</td>

  <td title="{{$item->clicks_count}} clicked">{{percentage($item->clicks_count, $item->emails_count)}}%</td>

  <td>
    @include('components.datatable.actions', ['actions' => [
        'other' => [
          ['route' => route('admin.subscriptions.reports.show', $item->list_id), 'title' => "More details", 'icon' => 'eye', 'target' => null]
        ]
    ]])
  </td>
</tr>