<div>
  @if(! empty($title))
  <h6 class="bg-light rounded w-100 p-3 mb-4"><strong>{{$title}}</strong></h6>
  @endif
  <div class="table-responsive">
    <table class="table {{! empty($borderless) ? 'table-borderless' : null}} {{empty($hoverable) ? 'table-hover' : null}} w-100 border {{!empty($sortable) && $sortable == true ? 'table-sortable' : null }}" id="{{$id ?? null}}" style="min-width: {{! empty($minWidth) ? $minWidth . 'px' : null }}">
      <thead>
        <tr>
          @foreach($headers as $label)
          <th class="border-0" scope="col">{!! $label !!}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        {!! $rows !!}
      </tbody>
    </table>
  </div>
</div>