<div class="col-12 p-3">
  <div class="border py-4 px-3">
    <div class="ml-2 mb-4">
      <h4 class="mb-1"><strong>Views</strong></h4>
      <p class="text-muted">Ranking of the number of times each quiz was viewed and completed.</p>
    </div>
    <div class="px-2">
      <table class="table table-hover" id="quizzes-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Date</th>
            <th class="border-0" scope="col">Title</th>
            <th class="border-0" scope="col">Views</th>
            <th class="border-0" scope="col">Completed</th>
            <th class="border-0" scope="col"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($quizzes as $quiz)
          <tr>
            @if($quiz->isPublished())
            <td title="Published at {{$quiz->published_at->format('g:i:s a')}}">{{$quiz->published_at->toFormattedDateString()}}</td>
            @else
            <td class="text-warning">Unpublished</td>
            @endif
            <td>{{$quiz->title}}</td>
            <td>{{$quiz->views}}</td>
            <td>{{$quiz->results_count}}</td>
            <td class="text-right">
              <a href="{{route('quizzes.show', $quiz->slug)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
              <a href="{{route('admin.quizzes.edit', $quiz->slug)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>