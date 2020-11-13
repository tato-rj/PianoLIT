<input type="hidden" name="subscription_name" placeholder="Your name here">
<input type="hidden" name="origin_url" value="{{url()->current()}}">
<input type="hidden" name="started_at" value="{{now()}}">
<input type="hidden" name="gift_url" value="{{$gift_url ?? null}}">
<input type="hidden" name="form_id" value="{{$id ?? null}}">