<div class="modal fade" id="famous-birthdays" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{$composers->where('is_famous', true)->count()}} Famous birthdays</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Here is a list of the birthdays of the most famous composers. An email will be generated for <u>each one</u> on its respective day.</p>
        <table class="table table-sm table-hover table-borderless">
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
              <td>{{$composer->short_name}}</td>
              <td>{{$composer->date_of_birth->toFormattedDateString()}}</td>
              <td class="text-right"><a href="{{route('email-preview.birthday', ['composer_id' => $composer->id])}}" target="_blank" title="See a preview of the birthday email" class="text-muted mr-2"><i class="fas fa-birthday-cake"></i></a></td>
            </tr>
          </tbody>
          @endforeach
          @endforeach
        </table>
      </div>
    </div>
  </div>
</div>