@foreach($pieces as $piece)
  @if($loop->iteration <= $limit)
  <tr>
    <td class="dataTables_main_column">{{$piece->medium_name_with_composer}}</td>
  </tr>
  @else
    @if(!empty($more))
    <tr>
      <td colspan="3" class="p-0">
        <button data-url="{{route('admin.users.load-favorites', $user->id)}}" class="btn border-0 btn-light rounded-0 btn-block load-more">
          LOAD MORE
        </button>
      </td>
    </tr>
    @endif
    @break
  @endif
@endforeach