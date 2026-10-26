@isset($gift)
    @include('components.download-offer', [
        'id' => 'modal-gift', 'cookie' => 'subscription-popup',
        'image' => storage($gift->thumbnail_path), 'imageAlt' => $gift->name.' poster preview',
        'imageTitle' => $gift->name, 'heading' => 'A little inspiration, on us',
        'description' => 'Make room for more music. Enter your email and we’ll send you this free poster to download and print.',
        'giftUrl' => route('infographs.download', $gift->slug), 'actionLabel' => 'Email me the poster',
    ])
@endisset
