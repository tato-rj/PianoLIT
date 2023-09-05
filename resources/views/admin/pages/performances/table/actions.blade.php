  @component('components.datatable.actions', ['actions' => [
      'other' => [['route' => $item->video_url, 'title' => 'Watch video', 'icon' => 'eye']],
      'delete' => route('admin.users.performances.destroy', $item)
  ]])
{{--   <div>
    <button class="btn-raw text-muted align-middle" data-toggle="modal" data-target="#update-performance-{{$item->id}}-modal">@fa(['icon' => 'edit'])</button>

  @component('components.modal', [
    'id' => 'update-performance-'.$item->id.'-modal',
    'header' => 'Update performance'])
  @slot('body')
  <form method="POST" action="{{route('admin.users.performances.update', $item)}}">
    @csrf
    @method('PATCH')
    @input(['bag' => 'default', 'name' => 'video_url', 'placeholder' => 'Video URL', 'value' => $item->video_url])
    @input(['bag' => 'default', 'name' => 'thumbnail_url', 'placeholder' => 'Thumbnail URL', 'value' => $item->thumbnail_url])

    @submit(['label' => 'Update', 'block' => true])
  </form>
  @endslot
  @endcomponent
  </div> --}}
  @endcomponent