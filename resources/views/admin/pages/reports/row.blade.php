<tr>

  <td>{{carbon($item->sent_at)->toFormattedDateString()}}</td>
  
  <td class="text-truncate">{{$item->name}}</td>
  
  <td>{{$item->emails_count}}</td>

  <td>{{$item->delivered_count}} <span class="badge badge-light badge-pill">{{percentage($item->delivered_count, $item->emails_count)}}%</span></td>

  <td>{{$item->failed_count}} <span class="badge badge-light badge-pill">{{percentage($item->failed_count, $item->emails_count)}}%</span></td>

  <td>{{$item->opens_count}} <span class="badge badge-light badge-pill">{{percentage($item->opens_count, $item->emails_count)}}%</span></td>

  <td>{{$item->clicks_count}} <span class="badge badge-light badge-pill">{{percentage($item->clicks_count, $item->emails_count)}}%</span></td>

  <td>
    @include('components.datatable.actions', ['actions' => [
        'other' => [
          ['route' => route('admin.subscriptions.reports.show', $item->list_id), 'title' => "More details", 'icon' => 'eye', 'target' => null]
        ]
    ]])
  </td>
</tr>