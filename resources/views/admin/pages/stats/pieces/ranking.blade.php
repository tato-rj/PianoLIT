


{{-- <div class="col-12 p-3">
  <div class="border py-4 px-3">
    <div class="ml-2 mb-4">
      <h4 class="mb-1"><strong>Views</strong></h4>
      <p class="text-muted">Ranking of the number of times each piece was viewed in the app.</p>
    </div>
    <div class="px-2">
      <table class="table table-hover" id="pieces-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Title</th>
            <th class="border-0" scope="col" title="All views">Global views</th>
            <th class="border-0" scope="col" title="Excludes repeated views by the same user">Unique views</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pieces as $piece)
          <tr>
            <td>{{$piece->longName}}</td>
            <td>{{$piece->views->count()}}</td>
            <td>{{$piece->views->unique('user_id')->count()}}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div> --}}