<tr class="{{($item->read_at) ? 'opacity-4' : null}}">
  <td class="text-nowrap" style="vertical-align: inherit;">{{$item->created_at->toFormattedDateString()}}</td>
  
  <td class="dataTables_main_column" style="vertical-align: inherit;" title="This happened at {{$item->created_at->format('g:i:s a')}}">{!! $item->data['message'] !!}</td>
  
  <td class="text-right" style="white-space: nowrap;">
    <button class="btn btn-sm btn-outline-secondary m-0 mark-notification" style="display: {{$item->read_at ? 'inline-block' : 'none'}}" 
      data-url="{{route('admin.notifications.unread', ['ids' => [$item->id]])}}">Mark as unread</button>

    <button class="btn btn-sm btn-outline-success m-0 mark-notification" style="display: {{$item->read_at ? 'none' : 'inline-block'}}" 
      data-url="{{route('admin.notifications.read', ['ids' => [$item->id]])}}">Mark as read</button>
  </td>
</tr>