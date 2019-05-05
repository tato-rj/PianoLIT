<div class="col-12 p-3">
  <div class="border py-4 px-3">
    <div class="ml-2 mb-4">
      <h4 class="mb-1"><strong>Views</strong></h4>
      <p class="text-muted">Ranking of the number of times each post was viewed.</p>
    </div>
    <div class="px-2">
      <table class="table table-hover" id="posts-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Date</th>
            <th class="border-0" scope="col">Title</th>
            <th class="border-0" scope="col">Views</th>
              <th class="border-0" scope="col"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($posts as $post)
          <tr>
            @if($post->isPublished())
            <td title="Published at {{$post->published_at->format('g:i:s a')}}">{{$post->published_at->toFormattedDateString()}}</td>
            @else
            <td class="text-warning">Unpublished</td>
            @endif
            <td>{{$post->title}}</td>
            <td>{{$post->views}}</td>
            <td class="text-right">
              <a href="{{route('posts.show', $post->slug)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
              <a href="{{route('admin.posts.edit', $post->slug)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>