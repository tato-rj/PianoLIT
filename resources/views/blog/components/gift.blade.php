<button type="button" class="btn btn-secondary position-fixed rounded-pill d-inline-flex align-items-center gap-2" id="gift" style="bottom: 24px; right: 24px" data-bs-toggle="modal" data-bs-target="#blog-gift-modal" aria-controls="blog-gift-modal" aria-haspopup="dialog">
    @icon('gift', ['mr' => 0])Free download
</button>

@include('components.promotions.download-offer', [
    'id' => 'blog-gift-modal', 'image' => $post->gift_path, 'imageAlt' => 'Article gift preview',
    'heading' => 'A little extra for your piano time',
    'description' => 'Keep the inspiration going. Enter your email and we’ll send you the free download that goes with this story.',
    'giftUrl' => url($post->gift_path), 'actionLabel' => 'Email me the download',
])
