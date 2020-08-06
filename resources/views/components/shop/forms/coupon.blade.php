<div class="form-group">
	<div class="position-relative">
		<input type="text" name="coupon" placeholder="Have a coupon code?" class="form-control" maxlength="20">
		<div class="h-100 p-2 load-validation position-absolute" style="top: 0; right: 0; display: none;">
			<div class="rounded d-flex flex-center alert-yellow px-2 h-100" style="font-size: 80%">@fa(['icon' => 'hourglass-half'])Validating...</div>
		</div>
	</div>
	<div id="coupon-feedback" data-url="{{route('shop.validate-coupon')}}" class="d-block coupon-feedback"></div>
</div>