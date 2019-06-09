<button class="position-fixed bg-white shadow rounded-pill px-3 py-2 d-flex align-items-center border-0" id="gift" style="bottom: 40px; right: 30px">
	<span class="mr-2 text-muted"><small>We have a gift for you!</small></span>
	<i class="fa-1x fas fa-gift animated infinite bounce delay-4s" style="color: #E92C59"></i>
</button>
@include('components.overlays.gift', ['image' => $post->gift_path])