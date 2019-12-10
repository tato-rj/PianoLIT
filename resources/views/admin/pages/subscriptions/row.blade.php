<tr>
  <td style="white-space: nowrap;">{{$subscription->created_at->toFormattedDateString()}}</td>
  <td title="Subscribed at {{$subscription->created_at->format('g:i:s a')}}">{{$subscription->email}}</td>
  <td title="{{$subscription->origin_url}}" style="max-width: 280px" class="text-truncate">{{$subscription->origin_url}}</td>
  @foreach(\App\Subscription::lists() as $list)
  <td>@include('admin.components.toggle.subscription', ['list' => $list])</td>
  @endforeach
  <td class="text-right" style="white-space: nowrap;">
    <a href="mailto:{{$subscription->email}}" target="_blank" class="text-muted mr-2"><i class="far fa-envelope align-middle"></i></a>
    <a href="#" data-name="{{$subscription->email}}" data-url="{{route('subscriptions.destroy', $subscription->email)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
  </td>
</tr>