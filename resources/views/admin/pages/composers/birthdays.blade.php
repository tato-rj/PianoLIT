<div class="modal fade" id="famous-birthdays" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Famous birthdays</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>This is a list of the <strong>{{$composers->where('is_famous', true)->count()}} most famous</strong> composers. An email will be generated for <u>each one</u> on the respective birthday.</p>
        <table class="table table-sm table-hover table-borderless">
          @php($missingNext = true)
          @foreach($composers->where('is_famous', true)->sortBy('month_of_birth')->groupBy('month_of_birth') as $month => $list)
          <thead>
            <tr>
              <th scope="col">{{date("F", mktime(0, 0, 0, $month, 1))}}</th>
              <th scope="col"></th>
              <th scope="col"></th>
            </tr>
          </thead>
          @foreach($list->sortBy('day_of_birth') as $composer)
          <tbody>
            <tr>
              <td>
                {{$composer->short_name}}
                @if($missingNext && $composer->date_of_birth->format('md') > now()->format('md'))
                  @php($missingNext = false)
                  <span class="badge badge-success ml-1">next</span>
                @endif
              </td>
              <td>{{$composer->date_of_birth->toFormattedDateString()}}</td>
            </tr>
          </tbody>
          @endforeach
          @endforeach
        </table>
      </div>
    </div>
  </div>
</div>