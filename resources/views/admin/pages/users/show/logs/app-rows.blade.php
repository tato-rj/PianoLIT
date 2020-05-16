@foreach($logs as $date => $event)
  <tr>
    <td class="text-nowrap">{{carbon($date)->format('M j\\, Y \\a\\t h:i A')}}</td>
    <td class="dataTables_main_column">{{$event->url}}</td>
    <td>
      <button  
      @if(! empty($event->data))
      data="{{json_encode($event->data)}}"
      data-url="{{json_encode($event->url)}}"
      data-toggle="modal" 
      data-target="#modal-log-data"
      @endif
      class="bg-transparent border-0 {{empty($event->data) ? 'text-grey' : 'text-success'}}" title="{{empty($event->data) ? 'Nothing to show' : 'More details'}}">
      <i class="fas fa-archive"></i>
      </button>
    </td>
  </tr>
@endforeach