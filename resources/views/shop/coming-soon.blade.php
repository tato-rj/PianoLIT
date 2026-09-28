<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PianoLIT Shop | Opening Soon</title>
    <meta name="description" content="The PianoLIT shop will open soon.">
    <link rel="canonical" href="{{ route('shop.home') }}">

    @include('layouts.html.google.fonts')
    @include('layouts.html.theme')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <style>
        .shop-coming-soon {
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            letter-spacing: 0;
        }

        .shop-coming-soon main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 1.5rem 1rem 3rem;
        }

        .shop-illustration {
            display: block;
            width: 360px;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
        }

        .shop-message {
            font-family: var(--font-family-heading);
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-heading);
            line-height: var(--line-height-body);
            max-width: 24rem;
            margin: 0 auto .5rem;
        }

        @media (max-height: 700px) {
            .shop-illustration {
                width: 260px;
                margin-bottom: 1rem;
            }

            .shop-coming-soon main {
                padding-top: .5rem;
                padding-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body class="shop-coming-soon">
    @php($websiteUrl = (parse_url(config('app.url'), PHP_URL_SCHEME) ?: request()->getScheme()).'://'.config('app.short_url'))
    <header class="container py-4">
        <a href="{{ $websiteUrl }}" class="d-inline-flex align-items-center link-none" aria-label="PianoLIT home">
            @brandIcon
        </a>
    </header>

    <main aria-label="Shop opening soon">
        <div class="text-center">
            <img class="shop-illustration" src="{{ asset('images/shop/coming-soon.webp') }}" width="800" height="800"
                 alt="A tiny music shop with a blue-striped awning and a piano in the window" fetchpriority="high">
            <p class="shop-message">Coming up soon!</p>
            <a href="{{ $websiteUrl }}">@icon('arrow-left')Back to PianoLIT</a>
        </div>
    </main>
</body>
</html>
