<table class="table table-striped table-borderless">
  <tbody>
  	@include('projects/pianolit/users/show/list-item', [
  		'title' => 'Auto renewal status', 
  		'value' => $history->pending_renewal_info[0]->auto_renew_status ? 'On' : 'Off'])
  	@foreach($history->receipt as $field => $value)
	  	@if($field != 'in_app')
	  	@include('projects/pianolit/users/show/list-item', [
	  		'title' => ucfirst(str_replace('_', ' ', $field)), 
	  		'value' => (strpos($value, 'GMT') !== false) ? \Carbon\Carbon::parse(stripcslashes($value))->timezone(config('app.timezone'))->format('M dS, Y \a\t h:i A') : $value])
	  	@endif
  	@endforeach
  </tbody>
</table>

<div class="accordion mb-4" id="subscription-history-receipts">

	@foreach($history->latest_receipt_info as $receipt)
	<div class="card">
		<div class="card-header bg-pastel" id="receipt-{{$loop->iteration}}">
			<div class="d-flex justify-content-between cursor-pointer" data-toggle="collapse" data-target="#receipt-history-{{$loop->iteration}}">
				<strong><i class="fas fa-file-alt mr-2"></i>Receipt #{{$loop->iteration}}</strong>
			</div>
		</div>

		<div id="receipt-history-{{$loop->iteration}}" class="collapse" aria-labelledby="receipt-{{$loop->iteration}}" data-parent="#subscription-history-receipts">
			<div class="card-body" style="background-color: rgba(255,251,241, 0.4)">
				<table class="table table-hover table-sm table-borderless m-0">
					<tbody>
						@foreach($receipt as $field => $value)
						  	@include('projects/pianolit/users/show/list-item', [
						  		'title' => ucfirst(str_replace('_', ' ', $field)), 
						  		'value' => (strpos($value, 'GMT') !== false) ? \Carbon\Carbon::parse(stripcslashes($value))->timezone(config('app.timezone'))->format('M dS, Y \a\t h:i A') : ucfirst($value)])
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
	@endforeach
</div>