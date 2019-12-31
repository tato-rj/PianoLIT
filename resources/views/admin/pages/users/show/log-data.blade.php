<table class="table table-borderless">
  <thead>
    <tr>
      <th scope="col">Key</th>
      <th scope="col">Value</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $key => $value)
    <tr>
      <th scope="row">{{snake_str($key)}}</th>
      <td>{{is_array($value) ? arrayToSentence($value) : $value}}</td>
    </tr>
    @endforeach
  </tbody>
</table>