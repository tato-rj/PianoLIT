<div class="position-relative">
<div class="badge-popup cursor-pointer badge badge-pill bg-{{strtolower($item->level->name)}}" 
    data-original-class="bg-{{strtolower($item->level->name)}}" 
    data-original-id="{{$item->level->id}}" 
    id="badge-level-{{$item->id}}">{{ucfirst($item->extendedLevel->name)}}</div>
<div class="position-absolute bg-white shadow-sm border px-2 pt-2 pb-1 rounded popup mb-3"  data-url="{{route('admin.pieces.load-levels', $item->id)}}" style="top: 10px; display: none; z-index: 1; left: 0">
@include('admin.pages.pieces.popups.content')
</div>
</div>