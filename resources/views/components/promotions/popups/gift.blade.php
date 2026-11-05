@isset($gift)
    @include('components.promotions.download-offer', [
        'id' => 'modal-gift', 'cookie' => 'subscription-popup',
        'image' => storage($gift->thumbnail_path), 'imageAlt' => $gift->name.' poster preview',
        'imageTitle' => $gift->name, 'heading' => 'Free printable poster',
        'description' => 'Enter your email to receive this free poster to download and print.',
        'giftUrl' => route('infographs.download', $gift->slug), 'actionLabel' => 'Email me the poster',
    ])
@endisset
