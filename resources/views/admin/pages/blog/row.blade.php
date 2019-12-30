<tr>
  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="dataTables_main_column">{{$item->title}}
    @if($item->hasGift())
    <span class="ml-2 gift position-relative"><i class="fas fa-gift" style="color: #E92C59"></i></span>
    @endif
  </td>

  <td>{{$item->reading_time}} min</td>
  
  <td>
    @toggle(['toggle' => $item->published_at, 'route' => route('admin.posts.update-status', $item->slug)])
  </td>

  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('posts.show', $item->slug), 'title' => 'Preview this post', 'icon' => 'eye']],
      'edit' => route('admin.posts.edit', $item->slug),
      'delete' => route('admin.posts.destroy', $item->slug)
  ]])
</tr>