<input type="hidden" name="notification_type" value="{{$event}}">
<input type="hidden" name="original_transaction_id" value="{{$user->membership->original_transaction_id}}">
<input type="hidden" name="environment" value="Sandbox">

@if($successful)
	<input type="hidden" name="latest_receipt" value="{{$user->membership->latest_receipt}}">
	<input type="hidden" name="latest_receipt_info" value="{{json_encode($user->membership->latest_receipt_info)}}">
@else
	<input type="hidden" name="latest_expired_receipt" value="{{$user->membership->latest_receipt}}">
	<input type="hidden" name="latest_expired_receipt_info" value="{{json_encode($user->membership->latest_receipt_info)}}">
@endif

<input type="hidden" name="auto_renew_adam_id" value="appleId">
<input type="hidden" name="auto_renew_product_id" value="productId">

@if($event == 'DID_CHANGE_RENEWAL_PREF')
	<input type="hidden" name="auto_renew_status" value="{{$user->membership->auto_renew_status ? 0 : 1}}">
	<input type="hidden" name="expiration_intent" value="{{$user->membership->auto_renew_status ? rand(1,5) : 0}}">
@elseif($event == 'RENEWAL' && $successful)
	<input type="hidden" name="auto_renew_status" value="1">
	<input type="hidden" name="expiration_intent" value="0">
@elseif($event == 'RENEWAL' && ! $successful)
	<input type="hidden" name="auto_renew_status" value="0">
	<input type="hidden" name="expiration_intent" value="2">
@else
	<input type="hidden" name="auto_renew_status" value="{{$user->membership->auto_renew_status ?? 1}}">
	<input type="hidden" name="expiration_intent" value="{{$user->membership->expiration_intent ?? 0}}">
@endif

@if($event == 'CANCEL' || ! $successful)
	<input type="hidden" name="cancellation_date" value="{{\Carbon\Carbon::now()->format('Y-m-d h:i:s e')}}">
@endif
