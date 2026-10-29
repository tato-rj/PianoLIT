@component('mail::message')
# Thank you for your purchase!

Hi {{ $user->first_name }}, your purchase is confirmed. Your files are ready to download.

@php($productImage = method_exists($item, 'mockup_image') ? ($item->mockup_image() ?: ($item->cover_path ? $item->cover_image() : null)) : ($item->image ?? null))
@component('mail::shop.products', ['items' => [['name' => $item->title, 'image' => $productImage]]])
@endcomponent

@component('mail::button', ['url' => route('users.purchases')])
View product here
@endcomponent

If you have any questions just let us know, we're always here to help!

Best,<br>
{{ config('app.name') }}
@endcomponent
