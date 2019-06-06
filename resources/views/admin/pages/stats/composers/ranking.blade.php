<div class="col-12 p-3">
  <div class="border py-4 px-3">
    <div class="ml-2 mb-4">
      <h4 class="mb-1"><strong>Age</strong></h4>
      <p class="text-muted">Ranking of the composers by their dates of birth and death.</p>
    </div>
    <div class="px-2">
      <table class="table table-hover" id="composers-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Name</th>
            <th class="border-0" scope="col">Born in</th>
            <th class="border-0" scope="col">Died in</th>
            <th class="border-0" scope="col">Age</th>
          </tr>
        </thead>
        <tbody>
          @foreach($composers as $composer)
            @if($composer->date_of_birth)
            <tr>
              <td>{{$composer->name}}</td>
              <td>{{$composer->date_of_birth ? $composer->date_of_birth->toFormattedDateString() : null}}</td>
              <td>{{$composer->date_of_death ? $composer->date_of_death->toFormattedDateString() : null}}</td>
              <td>{{$composer->age}}</td>
            </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>