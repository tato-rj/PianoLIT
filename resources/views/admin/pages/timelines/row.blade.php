<tr>
  <td>{{$item->year}}</td>
  
  <td class="text-nowrap">{{slug_str($item->type)}}</td>
  
  <td>
    @if($item->url)
    <a href="{{$item->url}}" target="_blank" class="link-blue mr-1"><i class="fas fa-{{$item->getIcon($item->type)['icon']}}"></i></a>
    @endif
    {{$item->event}}
  </td>
  
  <td class="text-muted text-nowrap"><i><small>Created by {{$item->creator->name}}</small></i></td>
  
  <td>
    @component('components.datatable.actions', ['actions' => [
        'delete' => route('admin.timelines.destroy', $item->id)
    ]])
      <a href="#" 
      data-toggle="modal" 
      data-target="#event-modal" 
      data-type="{{$item->type}}" 
      data-year="{{$item->year}}" 
      data-event="{{$item->event}}" 
      data-url="{{$item->url}}" 
      data-edit-url="{{route('admin.timelines.update', $item->id)}}" 
      class="text-muted cursor-pointer mr-2 event" title="Edit"><i class="far fa-edit align-middle"></i></a>
    @endcomponent
  </td>
</tr>