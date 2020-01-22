<table class="table table-borderless">
  <thead class="thead-light">
    <tr>
      <th class="py-2 rounded-left" scope="col">Key</th>
      <th class="py-2 rounded-right" scope="col">Value</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $key => $value)
    <tr>
      <th scope="row">{{snake_str($key)}}</th>
      @if($url == route('api.pieces.find'))
      @php($value = \App\Piece::findOrFail($value)->medium_name)
      @endif
      <td style="width: 76%">{{is_array($value) ? arrayToSentence($value) : $value}}</td>
    </tr>
    @endforeach
  </tbody>
</table>