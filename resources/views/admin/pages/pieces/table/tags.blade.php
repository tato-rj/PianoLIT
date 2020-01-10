<div class="position-relative">
<span class="badge badge-light badge-popup cursor-pointer" id="badge-tag-{{$item->id}}">{{$item->tags_count}}</span>
<div class="position-absolute bg-white shadow-sm border p-2 rounded popup mb-3 tags-quick-edit" 
  data-url="{{route('admin.pieces.load-tags', $item->id)}}" 
  style="top: 10px; display: none; z-index: 2; right: 0; width: 720px">
  @include('admin.pages.pieces.popups.content')
</div>
</div>